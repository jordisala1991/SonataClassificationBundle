<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\ClassificationBundle\Tests;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\ClassificationBundle\Controller\CategoryAdminController;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @author Dariusz Markowicz <dmarkowicz77@gmail.com>
 */
class CategoryAdminControllerTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var AdminInterface
     */
    private $admin;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $template;

    /**
     * @var CsrfProviderInterface
     */
    private $csrfProvider;

    /**
     * @var CategoryAdminController
     */
    private $controller;

    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     * Based on Sonata\AdminBundle\Tests\Controller\CRUDControllerTest.
     */
    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->request = new Request();
        $this->pool = new Pool($this->container, 'title', 'logo.png');
        $this->pool->setAdminServiceIds(['foo.admin']);
        $this->request->attributes->set('_sonata_admin', 'foo.admin');
        $this->parameters = [];
        $this->template = '';

        // php 5.3 BC
        $params = &$this->parameters;
        $template = &$this->template;

        $templating = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine')
            ->setMethods([])
            ->setConstructorArgs([$this->container, []])
            ->getMock();

        $templating->expects($this->any())
            ->method('renderResponse')
            ->will($this->returnCallback(function (
                $view,
                array $parameters = [],
                Response $response = null
            ) use (
                &$params,
                &$template
            ) {
                $template = $view;

                if (null === $response) {
                    $response = new Response();
                }

                $params = $parameters;

                return $response;
            }));

        // php 5.3 BC
        $pool = $this->pool;
        $request = $this->request;

        $twig = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $formRenderer = $this->createMock(FormRenderer::class);

        if (method_exists(AppVariable::class, 'getToken')) {
            $formExtension = new FormExtension();
        } else {
            // Remove this else clause when dropping sf < 3.2
            $formExtension = new FormExtension($formRenderer);
        }

        $twig->expects($this->any())
            ->method('getExtension')
            ->will($this->returnCallback(function ($name) use ($formExtension) {
                switch ($name) {
                    case 'form':
                    case FormExtension::class:
                        return $formExtension;
                }
            }));

        $twig->expects($this->any())
            ->method('getRuntime')
            ->will($this->returnCallback(function ($name) use ($formRenderer) {
                switch ($name) {
                    case FormRenderer::class:
                        return $formRenderer;
                }
            }));

        // Prefer Symfony 2.x interfaces
        if (interface_exists('Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface')) {
            $this->csrfProvider = $this->getMockBuilder(
                'Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface'
            )
                ->getMock();

            $this->csrfProvider->expects($this->any())
                ->method('generateCsrfToken')
                ->will($this->returnCallback(function ($intention) {
                    return 'csrf-token-123_'.$intention;
                }));

            $this->csrfProvider->expects($this->any())
                ->method('isCsrfTokenValid')
                ->will($this->returnCallback(function ($intention, $token) {
                    if ($token == 'csrf-token-123_'.$intention) {
                        return true;
                    }

                    return false;
                }));
        } else {
            $this->csrfProvider = $this->getMockBuilder(
                'Symfony\Component\Security\Csrf\CsrfTokenManagerInterface'
            )
                ->getMock();

            $this->csrfProvider->expects($this->any())
                ->method('getToken')
                ->will($this->returnCallback(function ($intention) {
                    return new CsrfToken($intention, 'csrf-token-123_'.$intention);
                }));

            $this->csrfProvider->expects($this->any())
                ->method('isTokenValid')
                ->will($this->returnCallback(function (CsrfToken $token) {
                    if ($token->getValue() == 'csrf-token-123_'.$token->getId()) {
                        return true;
                    }

                    return false;
                }));
        }

        // php 5.3 BC
        $csrfProvider = $this->csrfProvider;

        $this->admin = $this->getMockBuilder('Sonata\ClassificationBundle\Admin\CategoryAdmin')
            ->disableOriginalConstructor()
            ->getMock();
        $admin = $this->admin;

        $this->categoryManager = $this->getMockBuilder('Sonata\ClassificationBundle\Entity\CategoryManager')
            ->disableOriginalConstructor()
            ->getMock();
        $categoryManager = $this->categoryManager;

        $this->contextManager = $this->getMockBuilder('Sonata\ClassificationBundle\Entity\ContextManager')
            ->disableOriginalConstructor()
            ->getMock();
        $contextManager = $this->contextManager;

        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($id) use (
                $pool,
                $admin,
                $request,
                $templating,
                $twig,
                $csrfProvider,
                $categoryManager,
                $contextManager
            ) {
                switch ($id) {
                    case 'sonata.admin.pool':
                        return $pool;
                    case 'request':
                        return $request;
                    case 'foo.admin':
                        return $admin;
                    case 'templating':
                        return $templating;
                    case 'twig':
                        return $twig;
                    case 'form.csrf_provider':
                    case 'security.csrf.token_manager':
                        return $csrfProvider;
                    case 'sonata.classification.manager.category':
                        return $categoryManager;
                    case 'sonata.classification.manager.context':
                        return $contextManager;
                }
            }));

        // php 5.3
        $tthis = $this;

        $this->container->expects($this->any())
            ->method('has')
            ->will($this->returnCallback(function ($id) use ($tthis) {
                if ('form.csrf_provider' == $id && Kernel::MAJOR_VERSION == 2 && null !== $tthis->getCsrfProvider()) {
                    return true;
                }

                if ('security.csrf.token_manager' == $id && Kernel::MAJOR_VERSION >= 3 && null !== $tthis->getCsrfProvider()) {
                    return true;
                }

                if ('templating' == $id) {
                    return true;
                }

                return false;
            }));

        $this->admin->expects($this->any())
            ->method('generateUrl')
            ->will(
                $this->returnCallback(
                    function ($name, array $parameters = [], $absolute = false) {
                        $result = $name;
                        if (!empty($parameters)) {
                            $result .= '?'.http_build_query($parameters);
                        }

                        return $result;
                    }
                )
            );

        $this->controller = new CategoryAdminController();
        $this->controller->setContainer($this->container);
    }

    protected function tearDown()
    {
        $this->controller = null;
    }

    public function testListActionWithoutFilter()
    {
        $this->request->query->set('hide_context', '0');

        $result = $this->controller->listAction($this->request);
        $this->assertInstanceOf(
            'Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertSame('tree?hide_context=0', $result->getTargetUrl());
    }

    /**
     * @dataProvider listActionData
     */
    public function testListAction($context)
    {
        $this->request->query->set('_list_mode', 'list');
        $this->request->query->set('filter', 'filter[context][value]='.($context ? $context : ''));

        $datagrid = $this->createMock('Sonata\AdminBundle\Datagrid\DatagridInterface');

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->once())
             ->method('createView')
             ->will($this->returnValue(new FormView()));

        $this->admin->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($datagrid));

        $datagrid->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        $datagrid->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue([
                'context' => [
                    'value' => $context ?: '',
                ],
            ]));

        $this->admin->expects($this->any())
            ->method('getPersistentParameter')
            ->will($this->returnValue($context));

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response',
            $this->controller->listAction($this->request));
    }

    public function listActionData()
    {
        return [
            'context' => ['default'],
            'no context' => [false],
        ];
    }

    /**
     * @dataProvider treeActionData
     */
    public function testTreeAction($context, $categories)
    {
        $datagrid = $this->createMock('Sonata\AdminBundle\Datagrid\DatagridInterface');

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue(new FormView()));

        $this->admin->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($datagrid));

        $datagrid->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->admin->expects($this->any())
            ->method('getPersistentParameter')
            ->will($this->returnValue('default'));

        if ($context) {
            $contextMock = $this->getContextMock($context);
            $this->request->query->set('context', $contextMock->getId());
            $this->contextManager->expects($this->any())
                ->method('find')
                ->will($this->returnValue($contextMock));
        } else {
            $this->request->query->remove('context');
            $this->contextManager->expects($this->any())
                ->method('find')
                ->will($this->returnValue(false));
        }

        $categoriesMock = [];
        foreach ($categories as $category) {
            $categoryMock = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Model\Category');
            $categoryMock->setName($category[0]);
            if ($category[1]) {
                $categoryMock->setContext($this->getContextMock($category[1]));
            }
            $categoryMock->setEnabled(true);
            $categoriesMock[$categoryMock->getContext()->getId()][] = $categoryMock;
        }

        $this->categoryManager->expects($this->any())
            ->method('getRootCategoriesSplitByContexts')
            ->will($this->returnValue($categoriesMock));

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response',
            $this->controller->treeAction($this->request));
    }

    public function treeActionData()
    {
        return [
            'context and no categories' => ['default', []],
            'no context and no categories' => [false, []],
            'context and categories' => ['default', [
                ['First Category', 'other'],
                ['Second Category', 'default'],
            ]],
            'no context and categories' => [false, [
                ['First Category', 'other'],
                ['Second Category', 'default'],
            ]],
        ];
    }

    public function getCsrfProvider()
    {
        return $this->csrfProvider;
    }

    private function getContextMock($id)
    {
        $contextMock = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Model\Context');
        $contextMock->expects($this->any())->method('getId')->will($this->returnValue($id));
        $contextMock->setName($id);
        $contextMock->setEnabled(true);

        return $contextMock;
    }
}
