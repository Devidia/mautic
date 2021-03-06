<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\ReportBundle\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Mautic Report Builder class.
 */
final class MauticReportBuilder implements ReportBuilderInterface
{
    /**
     * @var array
     */
    const OPERATORS = [
        'default' => [
            'eq'       => 'mautic.core.operator.equals',
            'gt'       => 'mautic.core.operator.greaterthan',
            'gte'      => 'mautic.core.operator.greaterthanequals',
            'lt'       => 'mautic.core.operator.lessthan',
            'lte'      => 'mautic.core.operator.lessthanequals',
            'neq'      => 'mautic.core.operator.notequals',
            'like'     => 'mautic.core.operator.islike',
            'notLike'  => 'mautic.core.operator.isnotlike',
            'empty'    => 'mautic.core.operator.isempty',
            'notEmpty' => 'mautic.core.operator.isnotempty',
        ],
        'bool' => [
            'eq'  => 'mautic.core.operator.equals',
            'neq' => 'mautic.core.operator.notequals',
        ],
        'int' => [
            'eq'  => 'mautic.core.operator.equals',
            'gt'  => 'mautic.core.operator.greaterthan',
            'gte' => 'mautic.core.operator.greaterthanequals',
            'lt'  => 'mautic.core.operator.lessthan',
            'lte' => 'mautic.core.operator.lessthanequals',
            'neq' => 'mautic.core.operator.notequals',
        ],
        'multiselect' => [
            'in'    => 'mautic.core.operator.in',
            'notIn' => 'mautic.core.operator.notin',
        ],
        'select' => [
            'eq'  => 'mautic.core.operator.equals',
            'neq' => 'mautic.core.operator.notequals',
        ],
        'text' => [
            'eq'       => 'mautic.core.operator.equals',
            'neq'      => 'mautic.core.operator.notequals',
            'empty'    => 'mautic.core.operator.isempty',
            'notEmpty' => 'mautic.core.operator.isnotempty',
            'like'     => 'mautic.core.operator.islike',
            'notLike'  => 'mautic.core.operator.isnotlike',
        ],
    ];

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var \Mautic\ReportBundle\Entity\Report
     */
    private $entity;

    /**
     * @var string
     */
    private $contentTemplate;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * MauticReportBuilder constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param Connection               $db
     * @param Report                   $entity
     */
    public function __construct(EventDispatcherInterface $dispatcher, Connection $db, Report $entity)
    {
        $this->entity     = $entity;
        $this->dispatcher = $dispatcher;
        $this->db         = $db;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidReportQueryException
     */
    public function getQuery(array $options)
    {
        $queryBuilder = $this->configureBuilder($options);

        if ($queryBuilder->getType() !== QueryBuilder::SELECT) {
            throw new InvalidReportQueryException('Only SELECT statements are valid');
        }

        return $queryBuilder;
    }

    /**
     * Gets the getContentTemplate path.
     *
     * @return string
     */
    public function getContentTemplate()
    {
        return $this->contentTemplate;
    }

    /**
     * Configures builder.
     *
     * This method configures the ReportBuilder. It has to return a configured Doctrine DBAL QueryBuilder.
     *
     * @param array $options Options array
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function configureBuilder(array $options)
    {
        // Trigger the REPORT_ON_GENERATE event to initialize the QueryBuilder
        /** @var ReportGeneratorEvent $event */
        $event = $this->dispatcher->dispatch(
            ReportEvents::REPORT_ON_GENERATE,
            new ReportGeneratorEvent($this->entity, $options, $this->db->createQueryBuilder())
        );

        // Build the QUERY
        $queryBuilder = $event->getQueryBuilder();

        // Set Content Template
        $this->contentTemplate = $event->getContentTemplate();

        // Build WHERE clause
        $filtersApplied = false;
        if (isset($options['dynamicFilters'])) {
            $filtersApplied = $this->applyFilters($options['dynamicFilters'], $queryBuilder, $options['filters']);
        }

        if (!$filtersApplied) {
            if (!$filterExpr = $event->getFilterExpression()) {
                $this->applyFilters($this->entity->getFilters(), $queryBuilder, $options['filters']);
            } else {
                $queryBuilder->andWhere($filterExpr);
            }
        }

        // Build ORDER BY clause
        if (!empty($options['order'])) {
            if (is_array($options['order'])) {
                if (isset($o['column'])) {
                    $queryBuilder->orderBy($options['order']['column'], $options['order']['direction']);
                } elseif (!empty($options['order'][0][1])) {
                    list($column, $dir) = $options['order'];
                    $queryBuilder->orderBy($column, $dir);
                } else {
                    foreach ($options['order'] as $order) {
                        $queryBuilder->orderBy($order);
                    }
                }
            } else {
                $queryBuilder->orderBy($options['order']);
            }
        } elseif ($order = $this->entity->getTableOrder()) {
            foreach ($order as $o) {
                if (!empty($o['column'])) {
                    $queryBuilder->orderBy($o['column'], $o['direction']);
                }
            }
        }

        // Build GROUP BY
        if (!empty($options['groupby'])) {
            if (is_array($options['groupby'])) {
                foreach ($options['groupby'] as $groupBy) {
                    $queryBuilder->addGroupBy($groupBy);
                }
            } else {
                $queryBuilder->groupBy($options['groupby']);
            }
        }

        // Build LIMIT clause
        if (!empty($options['limit'])) {
            $queryBuilder->setFirstResult($options['start'])
                ->setMaxResults($options['limit']);
        }

        if (!empty($options['having'])) {
            if (is_array($options['having'])) {
                foreach ($options['having'] as $having) {
                    $queryBuilder->andHaving($having);
                }
            } else {
                $queryBuilder->having($options['having']);
            }
        }

        // Generate a count query in case a formula needs total number
        $countQuery = clone $queryBuilder;
        $countQuery->select('COUNT(*) as count');
        $countSql = sprintf('(%s)', $countQuery->getSQL());

        // Build SELECT clause
        if (!$selectColumns = $event->getSelectColumns()) {
            $fields = $this->entity->getColumns();
            foreach ($fields as $field) {
                if (isset($options['columns'][$field])) {
                    $select = '';
                    $select .= (isset($options['columns'][$field]['formula'])) ? $options['columns'][$field]['formula'] : $field;

                    if (strpos($select, '{{count}}')) {
                        $select = str_replace('{{count}}', $countSql, $select);
                    }

                    if (isset($options['columns'][$field]['alias'])) {
                        $select .= ' AS '.$options['columns'][$field]['alias'];
                    }

                    $selectColumns[] = $select;
                }
            }
        }

        $queryBuilder->addSelect(implode(', ', $selectColumns));

        return $queryBuilder;
    }

    /**
     * @param array        $filters
     * @param QueryBuilder $queryBuilder
     * @param array        $filterDefinitions
     *
     * @return bool
     */
    private function applyFilters(array $filters, QueryBuilder $queryBuilder, array $filterDefinitions)
    {
        $expr       = $queryBuilder->expr();
        $filterExpr = $expr->andX();

        if (count($filters)) {
            foreach ($filters as $filter) {
                $exprFunction = isset($filter['expr']) ? $filter['expr'] : $filter['condition'];
                $paramName    = InputHelper::alphanum($filter['column']);
                switch ($exprFunction) {
                    case 'notEmpty':
                        $filterExpr->add(
                            $expr->isNotNull($filter['column'])
                        );
                        $filterExpr->add(
                            $expr->neq($filter['column'], $expr->literal(''))
                        );
                        break;
                    case 'empty':
                        $filterExpr->add(
                            $expr->isNull($filter['column'])
                        );
                        $filterExpr->add(
                            $expr->eq($filter['column'], $expr->literal(''))
                        );
                        break;
                    default:
                        if (trim($filter['value']) == '') {
                            // Ignore empty
                            break;
                        }

                        $columnValue = ":$paramName";
                        switch ($filterDefinitions[$filter['column']]['type']) {
                            case 'bool':
                            case 'boolean':
                                if ((int) $filter['value'] > 1) {
                                    // Ignore the "reset" value of "2"
                                    break;
                                }

                                $queryBuilder->setParameter($paramName, $filter['value'], 'boolean');
                                break;

                            case 'float':
                                $columnValue = (float) $filter['value'];
                                break;

                            case 'int':
                            case 'integer':
                                $columnValue = (int) $filter['value'];
                                break;

                            default:
                                $queryBuilder->setParameter($paramName, $filter['value']);
                        }

                        $filterExpr->add(
                            $expr->{$exprFunction}($filter['column'], $columnValue)
                        );
                }
            }
        }

        if ($filterExpr->count()) {
            $queryBuilder->andWhere($filterExpr);

            return true;
        }

        return false;
    }
}
