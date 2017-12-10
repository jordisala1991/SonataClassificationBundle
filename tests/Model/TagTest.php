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

namespace Sonata\ClassificationBundle\Tests\Model;

use PHPUnit\Framework\TestCase;
use Sonata\ClassificationBundle\Model\ContextInterface;
use Sonata\ClassificationBundle\Model\Tag;

/**
 * @author Dariusz Markowicz <dmarkowicz77@gmail.com>
 */
class TagTest extends TestCase
{
    public function testSetterGetter(): void
    {
        $time = new \DateTime();

        /** @var ContextInterface $context */
        $context = $this->createMock('Sonata\ClassificationBundle\Model\ContextInterface');

        /** @var Tag $tag */
        $tag = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Model\Tag');
        $tag->setName('Hello World');
        $tag->setCreatedAt($time);
        $tag->setUpdatedAt($time);
        $tag->setEnabled(true);
        $tag->setContext($context);

        $this->assertEquals('Hello World', $tag->getName());
        $this->assertEquals('Hello World', $tag->__toString());
        $this->assertEquals('hello-world', $tag->getSlug());
        $this->assertEquals($time, $tag->getCreatedAt());
        $this->assertEquals($time, $tag->getUpdatedAt());
        $this->assertTrue($tag->getEnabled());
        $this->assertEquals($context, $tag->getContext());

        $tag->setName('');
        $this->assertEquals('n-a', $tag->getSlug());
        $this->assertEquals('n/a', $tag->__toString());

        $tag->setName('Привет мир');
        $this->assertEquals('privet-mir', $tag->getSlug());
        $this->assertEquals('Привет мир', $tag->__toString());

        $tag->setSlug('Custom Slug');
        $this->assertEquals('custom-slug', $tag->getSlug());
    }

    public function testPreUpdate(): void
    {
        /** @var Tag $tag */
        $tag = $this->getMockForAbstractClass('Sonata\ClassificationBundle\Model\Tag');
        $tag->preUpdate();

        $this->assertInstanceOf('\DateTime', $tag->getUpdatedAt());
    }
}
