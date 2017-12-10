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

namespace Sonata\ClassificationBundle\Tests\Block\Service;

use Sonata\AdminBundle\Tests\Fixtures\Admin\TagAdmin;
use Sonata\BlockBundle\Test\AbstractBlockServiceTestCase;
use Sonata\BlockBundle\Test\FakeTemplating;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Sonata\ClassificationBundle\Model\TagManagerInterface;

/**
 * @author Christian Gripp <mail@core23.de>
 */
final class AbstractTagsBlockServiceTest extends AbstractBlockServiceTestCase
{
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var TagAdmin
     */
    private $tagAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templating = new FakeTemplating();
        $this->contextManager = $this->createMock('Sonata\ClassificationBundle\Model\ContextManagerInterface');
        $this->tagManager = $this->createMock('Sonata\ClassificationBundle\Model\TagManagerInterface');
        $this->tagAdmin = $this->getMockBuilder('Sonata\ClassificationBundle\Admin\TagAdmin')->disableOriginalConstructor()->getMock();
    }

    public function testDefaultSettings(): void
    {
        $blockService = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Block\Service\AbstractTagsBlockService', [
            'block.service', $this->templating, $this->contextManager, $this->tagManager, $this->tagAdmin,
        ]);
        $blockContext = $this->getBlockContext($blockService);

        $this->assertSettings([
            'title' => 'Tags',
            'tag' => false,
            'tagId' => null,
            'context' => null,
            'template' => 'SonataClassificationBundle:Block:base_block_tags.html.twig',
        ], $blockContext);
    }

    public function testLoad(): void
    {
        $tag = $this->getMockBuilder('Sonata\ClassificationBundle\Model\TagInterface')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $tag->expects($this->any())->method('getId')->will($this->returnValue(23));

        $this->tagManager->expects($this->any())
            ->method('find')
            ->with($this->equalTo('23'))
            ->will($this->returnValue($tag));

        $block = $this->createMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->any())
            ->method('getSetting')
            ->with($this->equalTo('tagId'))
            ->will($this->returnValue(23));
        $block->expects($this->once())
            ->method('setSetting')
            ->with($this->equalTo('tagId'), $this->equalTo($tag));

        $blockService = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Block\Service\AbstractTagsBlockService', [
            'block.service', $this->templating, $this->contextManager, $this->tagManager, $this->tagAdmin,
        ]);
        $blockService->load($block);
    }

    public function testPrePersist(): void
    {
        $tag = $this->getMockBuilder('Sonata\ClassificationBundle\Model\TagInterface')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $tag->expects($this->any())->method('getId')->will($this->returnValue(23));

        $block = $this->createMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->any())
            ->method('getSetting')
            ->with($this->equalTo('tagId'))
            ->will($this->returnValue($tag));
        $block->expects($this->once())
            ->method('setSetting')
            ->with($this->equalTo('tagId'), $this->equalTo(23));

        $blockService = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Block\Service\AbstractTagsBlockService', [
            'block.service', $this->templating, $this->contextManager, $this->tagManager, $this->tagAdmin,
        ]);
        $blockService->prePersist($block);
    }

    public function testPreUpdate(): void
    {
        $tag = $this->getMockBuilder('Sonata\ClassificationBundle\Model\TagInterface')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $tag->expects($this->any())->method('getId')->will($this->returnValue(23));

        $block = $this->createMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->any())
            ->method('getSetting')
            ->with($this->equalTo('tagId'))
            ->will($this->returnValue($tag));
        $block->expects($this->once())
            ->method('setSetting')
            ->with($this->equalTo('tagId'), $this->equalTo(23));

        $blockService = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Block\Service\AbstractTagsBlockService', [
            'block.service', $this->templating, $this->contextManager, $this->tagManager, $this->tagAdmin,
        ]);
        $blockService->preUpdate($block);
    }
}
