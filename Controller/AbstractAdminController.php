<?php

namespace Sidus\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Elastica\Query;
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
     * @return DataGrid
     * @throws \UnexpectedValueException
     */
    protected function getDataGrid()
    {
        return $this->get('sidus_data_grid.datagrid_configuration.handler')
            ->getDataGrid($this->getDataGridConfigCode());
    }

    /**
     * @param DataGrid $dataGrid
     * @param Request  $request
     * @param array    $formOptions
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
     * @return Form
     * @throws \InvalidArgumentException
     */
    protected function getForm(Request $request, $data, array $options = [])
    {
        $action = $this->admin->getCurrentAction();
        $defaultOptions = $this->getDefaultFormOptions($request, $data ? $data->getId() : 'new');

        return $this->createForm($action->getFormType(), $data, array_merge($defaultOptions, $options));
    }

    /**
     * @param mixed $data
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
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getDefaultFormOptions(Request $request, $dataId, Action $action = null)
    {
        if (!$action) {
            $action = $this->admin->getCurrentAction();
        }

        return [
            'action' => $this->getCurrentUri($request),
            'attr' => [
                'novalidate' => 'novalidate',
                'id' => "form_{$this->admin->getCode()}_{$action->getCode()}_{$dataId}",
            ],
            'method' => 'post',
        ];
    }

    /**
     * @param Admin  $admin
     * @param Action $action
     * @return \Twig_Template
     * @throws \Exception
     */
    protected function getTemplate(Admin $admin = null, Action $action = null)
    {
        return $this->container->get('sidus_admin.templating.template_resolver')->getTemplate($admin, $action);
    }

    /**
     * @param array       $parameters
     * @param Admin|null  $admin
     * @param Action|null $action
     * @return Response
     * @throws \Exception
     */
    protected function renderAction(array $parameters = [], Admin $admin = null, Action $action = null)
    {
        $response = new Response();
        $response->setContent($this->getTemplate($admin, $action)->render($parameters));

        return $response;
    }

    /**
     * @param Request                       $request
     * @param array|ParamConverterInterface $configuration
     * @return mixed
     * @throws \Exception
     */
    protected function getDataFromRequest(Request $request, $configuration = null)
    {
        if (null === $configuration) {
            $configuration = [
                new ParamConverter([
                    'name' => 'data',
                    'class' => $this->admin->getEntity(),
                ]),
            ];
        }
        $this->container->get('sensio_framework_extra.converter.manager')->apply($request, $configuration);

        return $request->attributes->get('data');
    }

    /**
     * @param mixed $entity
     * @param string $action
     * @param array $parameters
     * @param int $referenceType
     * @param int $status
     * @return RedirectResponse
     * @throws \Exception
     */
    protected function redirectToEntity(
        $entity,
        $action,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        $status = 302
    ) {
        $url = $this->container->get('sidus_admin.routing.admin_router')
            ->generateEntityPath($entity, $action, $parameters, $referenceType);

        return new RedirectResponse($url, $status);
    }

    /**
     * @param string $admin
     * @param string $action
     * @param array  $parameters
     * @param int    $referenceType
     * @param int    $status
     * @return RedirectResponse
     * @throws \Exception
     */
    protected function redirectToAdmin(
        $admin,
        $action,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        $status = 302
    ) {
        $url = $this->container->get('sidus_admin.routing.admin_router')
            ->generateAdminPath($admin, $action, $parameters, $referenceType);

        return new RedirectResponse($url, $status);
    }
    /**
     * @param Request $request
     * @param array   $parameters
     * @return string
     * @throws \InvalidArgumentException
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
     * @return EntityManager
     * @throws \InvalidArgumentException
     * @throws \LogicException
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
