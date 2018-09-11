<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Templating\TemplateResolverInterface;
use Sidus\DataGridBundle\Model\DataGrid;
use Sidus\DataGridBundle\Registry\DataGridRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sidus\AdminBundle\Routing\AdminRouter;

/**
 * This class should cover all the basic needs to create admin based controllers
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractAdminController extends Controller implements AdminInjectableInterface
{
    /** @var Admin */
    protected $admin;

    /**
     * @param Admin $admin
     */
    public function setAdmin(Admin $admin)
    {
        $this->admin = $admin;
    }

    /**
     * @return string
     */
    protected function getDataGridConfigCode(): string
    {
        // Check if datagrid code is set in options
        $datagridCode = $this->admin->getOption('datagrid');
        if ($datagridCode) {
            return $datagridCode;
        }

        return $this->admin->getCode();
    }

    /**
     * @throws \UnexpectedValueException
     * @throws \Sidus\FilterBundle\Exception\MissingQueryHandlerFactoryException
     * @throws \Sidus\FilterBundle\Exception\MissingQueryHandlerException
     * @throws \Sidus\FilterBundle\Exception\MissingFilterException
     * @throws \Symfony\Component\PropertyAccess\Exception\ExceptionInterface
     *
     * @return DataGrid
     */
    protected function getDataGrid(): DataGrid
    {
        return $this->get(DataGridRegistry::class)
            ->getDataGrid($this->getDataGridConfigCode());
    }

    /**
     * @param DataGrid $dataGrid
     * @param Request  $request
     * @param array    $formOptions
     *
     * @throws \Exception
     */
    protected function bindDataGridRequest(DataGrid $dataGrid, Request $request, array $formOptions = [])
    {
        $formOptions = array_merge(
            [
                'method' => $request->getMethod(),
                'csrf_protection' => false,
                'action' => $this->getCurrentUri($request),
                'validation_groups' => ['filters'],
            ],
            $formOptions
        );

        // Create form with filters
        $builder = $this->createFormBuilder(null, $formOptions);
        $dataGrid->buildForm($builder);
        $dataGrid->handleRequest($request);
    }

    /**
     * @param Request $request
     * @param mixed   $data
     * @param array   $options
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     *
     * @return FormInterface
     */
    protected function getForm(Request $request, $data, array $options = []): FormInterface
    {
        $action = $this->admin->getCurrentAction();
        $dataId = $data && method_exists($data, 'getId') ? $data->getId() : null;
        $defaultOptions = $this->getDefaultFormOptions($request, $dataId, $action);

        return $this->getFormBuilder($action, $data, array_merge($defaultOptions, $options))->getForm();
    }

    /**
     * @param Action $action
     * @param mixed  $data
     * @param array  $options
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     *
     * @return FormBuilderInterface
     */
    protected function getFormBuilder(Action $action, $data, array $options = []): FormBuilderInterface
    {
        if (!$action->getFormType()) {
            throw new \UnexpectedValueException("Missing parameter 'form_type' for action '{$action->getCode()}'");
        }

        return $this->get('form.factory')->createNamedBuilder(
            "form_{$this->admin->getCode()}_{$action->getCode()}",
            $action->getFormType(),
            $data,
            $options
        );
    }

    /**
     * @param mixed $data
     *
     * @throws \Exception
     */
    protected function saveEntity($data)
    {
        $entityManager = $this->getManagerForEntity($data);
        $entityManager->persist($data);
        $entityManager->flush();

        $action = $this->admin->getCurrentAction();
        $this->addFlash('success', $this->translate("admin.flash.{$action->getCode()}.success"));
    }

    /**
     * @param mixed $data
     *
     * @throws \Exception
     */
    protected function deleteEntity($data)
    {
        $entityManager = $this->getManagerForEntity($data);
        $entityManager->remove($data);
        $entityManager->flush();

        $action = $this->admin->getCurrentAction();
        $this->addFlash('success', $this->translate("admin.flash.{$action->getCode()}.success"));
    }

    /**
     * @param Request     $request
     * @param string      $dataId
     * @param Action|null $action
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function getDefaultFormOptions(Request $request, $dataId, Action $action = null)
    {
        if (!$action) {
            $action = $this->admin->getCurrentAction();
        }
        $dataId = $dataId ?: 'new';

        return array_merge(
            $action->getFormOptions(),
            [
                'action' => $this->getCurrentUri($request),
                'attr' => [
                    'novalidate' => 'novalidate',
                    'id' => "form_{$this->admin->getCode()}_{$action->getCode()}_{$dataId}",
                ],
                'method' => 'post',
            ]
        );
    }

    /**
     * @param Action $action
     * @param string $templateType
     *
     * @throws \Exception
     *
     * @return \Twig_Template
     */
    protected function getTemplate(Action $action = null, $templateType = 'html')
    {
        return $this->container->get(TemplateResolverInterface::class)->getTemplate(
            $action ?: $this->admin->getCurrentAction(),
            $templateType
        );
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @param array       $parameters
     * @param Action|null $action
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     *
     * @return Response
     */
    protected function renderAction(array $parameters = [], Action $action = null)
    {
        $response = new Response();
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $response->setContent($this->getTemplate($action)->render($parameters));

        return $response;
    }

    /**
     * @param Request                       $request
     * @param array|ParamConverterInterface $configuration
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function getDataFromRequest(Request $request, $configuration = null)
    {
        if (null === $configuration) {
            $configuration = [
                new ParamConverter(
                    [
                        'name' => 'data',
                        'class' => $this->admin->getEntity(),
                    ]
                ),
            ];
        }
        $this->container->get('sensio_framework_extra.converter.manager')->apply($request, $configuration);

        return $request->attributes->get('data');
    }

    /**
     * @param mixed  $entity
     * @param string $action
     * @param array  $parameters
     * @param int    $referenceType
     * @param int    $status
     *
     * @throws \Exception
     *
     * @return RedirectResponse
     */
    protected function redirectToEntity(
        $entity,
        $action,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        $status = 302
    ) {
        $url = $this->container->get(AdminRouter::class)
            ->generateAdminEntityPath($this->admin, $entity, $action, $parameters, $referenceType);

        return new RedirectResponse($url, $status);
    }

    /**
     * @param string $action
     * @param array  $parameters
     * @param int    $referenceType
     * @param int    $status
     *
     * @throws \Exception
     *
     * @return RedirectResponse
     */
    protected function redirectToAction(
        $action,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        $status = 302
    ) {
        $url = $this->container->get(AdminRouter::class)
            ->generateAdminPath($this->admin, $action, $parameters, $referenceType);

        return new RedirectResponse($url, $status);
    }

    /**
     * @param Request $request
     * @param array   $parameters
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function getCurrentUri(Request $request, array $parameters = [])
    {
        $params = $request->attributes->get('_route_params');

        return $this->generateUrl($request->attributes->get('_route'), array_merge($params, $parameters));
    }

    /**
     * Alias to return the entity manager
     *
     * @deprecated, use the getManagerForEntity method instead
     *
     * @param string|null $persistentManagerName
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     *
     * @return EntityManagerInterface
     */
    protected function getManager($persistentManagerName = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */

        return $this->getDoctrine()->getManager($persistentManagerName);
    }

    /**
     * @param mixed $entity
     *
     * @throws \LogicException
     *
     * @return EntityManagerInterface
     */
    protected function getManagerForEntity($entity)
    {
        $class = ClassUtils::getClass($entity);
        $entityManager = $this->getDoctrine()->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException("No manager found for class {$class}");
        }

        return $entityManager;
    }

    /**
     * Translates the given message.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     */
    protected function translate($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->get('translator')->trans($id, $parameters, $domain, $locale);
    }
}
