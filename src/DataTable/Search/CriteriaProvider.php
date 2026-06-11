<?php

namespace App\DataTable\Search;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\QueryBuilderProcessorInterface;
use Omines\DataTablesBundle\DataTableState;
use Psr\Log\LoggerInterface;

class CriteriaProvider implements QueryBuilderProcessorInterface
{
    public function process(QueryBuilder $queryBuilder, DataTableState $state): void
    {
        $this->processSearchColumns($queryBuilder, $state);
        
        $this->processGlobalSearch($queryBuilder, $state);
    }

    public function processSearchColumns(QueryBuilder $queryBuilder, DataTableState $state)
    {
        
        foreach ($state->getSearchColumns() as $searchInfo) {
            $column = $searchInfo['column'];
            $search = $searchInfo['search'];
            if ('' !== trim($search) && $search !== false && $search !== null) {
                if(method_exists($column, 'getProcessSearch') && null !== $column->getProcessSearch()){
                    call_user_func($column->getProcessSearch(), $queryBuilder, $state, $search);
                }else{
                    $rightExpr = $column->getRightExpr($search);
                    if($rightExpr !== null && '' !== trim($rightExpr)){
                        $rightExpr = $queryBuilder->expr()->literal($rightExpr);
                        $com = new Comparison( $column->getLeftExpr(), $column->getOperator(), $rightExpr);
                        $queryBuilder->andWhere($com);
                    }
                }
            }
            
        }  
    }

    private function processGlobalSearch(QueryBuilder $queryBuilder, DataTableState $state)
    {
        if (!empty($globalSearch = $state->getGlobalSearch())) {
            $expr = $queryBuilder->expr();
            $comparisons = $expr->orX();
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable()){
                    if($column->isValidForSearch($globalSearch)){
                        if(method_exists($column, 'getProcessGlobalSearch')){
                            if(null !== $column->getProcessGlobalSearch()){
                                call_user_func($column->getProcessGlobalSearch(), $queryBuilder, $state, $globalSearch);
                            }
                        }else{
                            if(!empty($column->getField())){
                                if($column->getRightExpr($globalSearch) !== null){
                                    $comparisons->add(
                                        new Comparison(
                                            $column->getLeftExpr(), 
                                            $column->getOperator(),
                                            $expr->literal($column->getRightExpr($globalSearch))
                                        )
                                    );
                                }
                            }
                        }
                    }
                    
                }
            }
            $queryBuilder->andWhere($comparisons);
        }
    }
}
