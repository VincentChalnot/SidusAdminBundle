<?php

namespace Sidus\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Sidus\DataGridBundle\Model\DataGrid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    protected function getDataGridConfigCode()
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
     *
     * @return DataGrid
     */
    protected function getDataGrid()
    {
        return $this->get('sidus_data_grid.registry.datagrid')
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
     * @return Form
     */
    protected function getForm(Request $request, $data, array $options = [])
    {
        $action = $this->admin->getCurrentAction();
        if (!$action->getFormType()) {
            throw new \UnexpectedValueException("Missing parameter 'form_type' for action '{$action->getCode()}'");
        }

        $dataId = $data && method_exists($data, 'getId') ? $data->getId() : null;
        $defaultOptions = $this->getDefaultFormOptions($request, $dataId, $action);

        $builder = $this->get('form.factory')->createNamedBuilder(
            "form_{$this->admin->getCode()}_{$action->getCode()}",
            $action->getFormType(),
            $data,
            array_merge($defaultOptions, $options)
        );

        return $builder->getForm();
    }

    /**
     * @param mixed $data
     *
     * @throws \Exception
     */
    protected function saveEntity($data)
    {
        $em = $this->getManager();
        $em->persist($data);
        $em->flush();

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
        $em = $this->getManager();
        $em->remove($data);
        $em->flush();

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
        return $this->container->get('sidus_admin.templating.template_resolver')->getTemplate(
            $this->admin,
            $action,
            $templateType
        );
    }

    /**
     * @param array       $parameters
     * @param Action|null $action
     *
     * @throws \Exception
     *
     * @return Response
     */
    protected function renderAction(array $parameters = [], Action $action = null)
    {
        $response = new Response();
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
        $url = $this->container->get('sidus_admin.routing.admin_router')
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
        $url = $this->container->get('sidus_admin.routing.admin_router')
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
     * @param string|null $persistentManagerName
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     *
     * @return EntityManager
     */
    protected function getManager($persistentManagerName = null)
    {
        return $this->getDoctrine()->getManager($persistentManagerName);
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
    protected function translate($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->get('translator')->trans($id, $parameters, $domain, $locale);
    }
}
