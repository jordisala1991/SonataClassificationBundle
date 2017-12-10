<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\ClassificationBundle\Tests\Controller\Api;

use PHPUnit\Framework\TestCase;
use Sonata\ClassificationBundle\Controller\Api\ContextController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Thomas Rabaix <thomas.rabaix@gmail.com>
 */
class ContextControllerTest extends TestCase
{
    public function testGetContextsAction(): void
    {
        $paramFetcher = $this->createMock('FOS\RestBundle\Request\ParamFetcherInterface');
        $paramFetcher->expects($this->once())->method('all')->will($this->returnValue([]));

        $pager = $this->getMockBuilder('Sonata\AdminBundle\Datagrid\Pager')->disableOriginalConstructor()->getMock();

        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->once())->method('getPager')->will($this->returnValue($pager));

        $this->assertSame($pager, $this->createContextController($contextManager)->getContextsAction($paramFetcher));
    }

    public function testGetContextAction(): void
    {
        $context = $this->createMock('Sonata\ClassificationBundle\Model\ContextInterface');

        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->once())->method('find')->will($this->returnValue($context));

        $this->assertEquals($context, $this->createContextController($contextManager)->getContextAction(1));
    }

    public function testGetContextNotFoundExceptionAction(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Context (42) not found');

        $this->createContextController()->getContextAction(42);
    }

    public function testPostContextAction(): void
    {
        $context = $this->createMock('Sonata\ClassificationBundle\Model\ContextInterface');

        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->once())->method('save')->will($this->returnValue($context));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('handleRequest');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(true));
        $form->expects($this->once())->method('getData')->will($this->returnValue($context));
        $form->expects($this->once())->method('all')->will($this->returnValue([]));

        $formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createContextController($contextManager, $formFactory)->postContextAction(new Request());

        $this->assertInstanceOf('FOS\RestBundle\View\View', $view);
    }

    public function testPostContextInvalidAction(): void
    {
        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->never())->method('save')->will($this->returnValue($contextManager));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('handleRequest');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(false));
        $form->expects($this->once())->method('all')->will($this->returnValue([]));

        $formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createContextController($contextManager, $formFactory)->postContextAction(new Request());

        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $view);
    }

    public function testPutContextAction(): void
    {
        $context = $this->createMock('Sonata\ClassificationBundle\Model\ContextInterface');

        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->once())->method('find')->will($this->returnValue($context));
        $contextManager->expects($this->once())->method('save')->will($this->returnValue($context));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('handleRequest');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(true));
        $form->expects($this->once())->method('getData')->will($this->returnValue($context));
        $form->expects($this->once())->method('all')->will($this->returnValue([]));

        $formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createContextController($contextManager, $formFactory)->putContextAction(1, new Request());

        $this->assertInstanceOf('FOS\RestBundle\View\View', $view);
    }

    public function testPutPostInvalidAction(): void
    {
        $context = $this->createMock('Sonata\ClassificationBundle\Model\ContextInterface');

        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->once())->method('find')->will($this->returnValue($context));
        $contextManager->expects($this->never())->method('save')->will($this->returnValue($context));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('handleRequest');
        $form->expects($this->once())->method('isValid')->will($this->returnValue(false));
        $form->expects($this->once())->method('all')->will($this->returnValue([]));

        $formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->once())->method('createNamed')->will($this->returnValue($form));

        $view = $this->createContextController($contextManager, $formFactory)->putContextAction(1, new Request());

        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $view);
    }

    public function testDeleteContextAction(): void
    {
        $context = $this->createMock('Sonata\ClassificationBundle\Model\ContextInterface');

        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->once())->method('find')->will($this->returnValue($context));
        $contextManager->expects($this->once())->method('delete');

        $view = $this->createContextController($contextManager)->deleteContextAction(1);

        $this->assertEquals(['deleted' => true], $view);
    }

    public function testDeleteContextInvalidAction(): void
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');

        $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $contextManager->expects($this->once())->method('find')->will($this->returnValue(null));
        $contextManager->expects($this->never())->method('delete');

        $this->createContextController($contextManager)->deleteContextAction(1);
    }

    /**
     * Creates a new ContextController.
     *
     * @param null $contextManager
     * @param null $formFactory
     *
     * @return ContextController
     */
    protected function createContextController($contextManager = null, $formFactory = null)
    {
        if (null === $contextManager) {
            $contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        }
        if (null === $formFactory) {
            $formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        }

        return new ContextController($contextManager, $formFactory);
    }
}
