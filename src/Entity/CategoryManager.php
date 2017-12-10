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

namespace Sonata\ClassificationBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sonata\ClassificationBundle\Model\CategoryInterface;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\ContextInterface;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Sonata\CoreBundle\Model\BaseEntityManager;
use Sonata\DatagridBundle\Pager\Doctrine\Pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;

class CategoryManager extends BaseEntityManager implements CategoryManagerInterface
{
    /**
     * @var array
     */
    protected $categories;

    /**
     * @var ContextManagerInterface
     */
    protected $contextManager;

    /**
     * @param string                  $class
     * @param ManagerRegistry         $registry
     * @param ContextManagerInterface $contextManager
     */
    public function __construct($class, ManagerRegistry $registry, ContextManagerInterface $contextManager)
    {
        parent::__construct($class, $registry);

        $this->contextManager = $contextManager;
        $this->categories = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategoriesPager($page = 1, $limit = 25, $criteria = [])
    {
        $page = 0 == (int) $page ? 1 : (int) $page;

        $queryBuilder = $this->getObjectManager()->createQueryBuilder()
            ->select('c')
            ->from($this->class, 'c')
            ->andWhere('c.parent IS NULL');

        $pager = new Pager($limit);
        $pager->setQuery(new ProxyQuery($queryBuilder));
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubCategoriesPager($categoryId, $page = 1, $limit = 25, $criteria = [])
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder()
            ->select('c')
            ->from($this->class, 'c')
            ->where('c.parent = :categoryId')
            ->setParameter('categoryId', $categoryId);

        $pager = new Pager($limit);
        $pager->setQuery(new ProxyQuery($queryBuilder));
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategoryWithChildren(CategoryInterface $category)
    {
        if (null === $category->getContext()) {
            throw new \RuntimeException('Context cannot be null');
        }
        if (null != $category->getParent()) {
            throw new \RuntimeException('Method can be called only for root categories');
        }
        $context = $this->getContext($category->getContext());

        $this->loadCategories($context);

        foreach ($this->categories[$context->getId()] as $contextRootCategory) {
            if ($category->getId() == $contextRootCategory->getId()) {
                return $contextRootCategory;
            }
        }

        throw new \RuntimeException('Category does not exist');
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategory($context = null)
    {
        $context = $this->getContext($context);

        $this->loadCategories($context);

        return $this->categories[$context->getId()][0];
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategoriesForContext(ContextInterface $context = null)
    {
        $context = $this->getContext($context);

        $this->loadCategories($context);

        return $this->categories[$context->getId()];
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategories($loadChildren = true)
    {
        $class = $this->getClass();

        $rootCategories = $this->getObjectManager()->createQuery(sprintf('SELECT c FROM %s c WHERE c.parent IS NULL', $class))
            ->execute();

        $categories = [];

        foreach ($rootCategories as $category) {
            if (null === $category->getContext()) {
                throw new \RuntimeException('Context cannot be null');
            }

            $categories[$category->getContext()->getId()] = $loadChildren ? $this->getRootCategory($category->getContext()) : $category;
        }

        return $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllRootCategories($loadChildren = true)
    {
        $class = $this->getClass();

        $rootCategories = $this->getObjectManager()->createQuery(sprintf('SELECT c FROM %s c WHERE c.parent IS NULL', $class))
            ->execute();

        $categories = [];

        foreach ($rootCategories as $category) {
            if (null === $category->getContext()) {
                throw new \RuntimeException('Context cannot be null');
            }

            $categories[] = $loadChildren ? $this->getRootCategoryWithChildren($category) : $category;
        }

        return $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategoriesSplitByContexts($loadChildren = true)
    {
        $rootCategories = $this->getAllRootCategories($loadChildren);

        $splitCategories = [];

        foreach ($rootCategories as $category) {
            $splitCategories[$category->getContext()->getId()][] = $category;
        }

        return $splitCategories;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories($context = null)
    {
        $context = $this->getContext($context);

        $this->loadCategories($context);

        return $this->categories[$context->getId()];
    }

    /**
     * {@inheritdoc}
     */
    public function getPager(array $criteria, $page, $limit = 10, array $sort = [])
    {
        $parameters = [];

        $query = $this->getRepository()
            ->createQueryBuilder('c')
            ->select('c');

        if (isset($criteria['context'])) {
            $query->andWhere('c.context = :context');
            $parameters['context'] = $criteria['context'];
        }

        if (isset($criteria['enabled'])) {
            $query->andWhere('c.enabled = :enabled');
            $parameters['enabled'] = (bool) $criteria['enabled'];
        }

        $query->setParameters($parameters);

        $pager = new Pager();
        $pager->setMaxPerPage($limit);
        $pager->setQuery(new ProxyQuery($query));
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }

    /**
     * Load all categories from the database, the current method is very efficient for < 256 categories.
     *
     * @param ContextInterface $context
     */
    protected function loadCategories(ContextInterface $context): void
    {
        if (array_key_exists($context->getId(), $this->categories)) {
            return;
        }

        $class = $this->getClass();

        $categories = $this->getObjectManager()->createQuery(sprintf('SELECT c FROM %s c WHERE c.context = :context ORDER BY c.parent ASC', $class))
            ->setParameter('context', $context->getId())
            ->execute();

        if (0 == count($categories)) {
            // no category, create one for the provided context
            $category = $this->create();
            $category->setName($context->getName());
            $category->setEnabled(true);
            $category->setContext($context);
            $category->setDescription($context->getName());

            $this->save($category);

            $categories = [$category];
        }

        $rootCategories = [];
        foreach ($categories as $pos => $category) {
            if (null === $category->getParent()) {
                $rootCategories[] = $category;
            }

            $this->categories[$context->getId()][$category->getId()] = $category;

            $parent = $category->getParent();

            $category->disableChildrenLazyLoading();

            if ($parent) {
                $parent->addChild($category);
            }
        }

        $this->categories[$context->getId()] = $rootCategories;
    }

    /**
     * @param $contextCode
     *
     * @return ContextInterface
     */
    private function getContext($contextCode)
    {
        if (empty($contextCode)) {
            $contextCode = ContextInterface::DEFAULT_CONTEXT;
        }

        if ($contextCode instanceof ContextInterface) {
            return $contextCode;
        }

        $context = $this->contextManager->find($contextCode);

        if (!$context instanceof ContextInterface) {
            $context = $this->contextManager->create();

            $context->setId($contextCode);
            $context->setName($contextCode);
            $context->setEnabled(true);

            $this->contextManager->save($context);
        }

        return $context;
    }
}
