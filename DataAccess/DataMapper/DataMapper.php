<?php

namespace DataAccess\DataMapper;

use Domain\Entities\IDivineEntity;
use DataAccess\DataMapper\IDataMapper;
use DataAccess\DataMapper\Helpers\AbstractPopulationHelper;
use ReflectionClass;
use PDO;

class DataMapper implements IDataMapper
{
    private $_db;
    private $_maps;
    
    public function __construct($maps)
    {
        //TODO: should probably do all this through a configuration object or something
        $dsn = 'mysql:host=localhost;dbname=divinelegy;charset=utf8';
        $username = 'root';
        $password = 'toor';
        $options = array(PDO::ATTR_EMULATE_PREPARES => false,
                         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        
        $this->_db = new PDO($dsn, $username, $password, $options);        
        $this->_maps = include $maps;
    }
    
    public function find($id, $entity)
    {
        $statement = $this->_db->prepare(sprintf('SELECT * from %s WHERE id=%u',
            $this->_maps[$entity]['table'],
            $id));
        $statement->execute();
        $row = $statement->fetch();
                
        $className = $this->_maps[$entity]['class']; //the entity to instantiate and return
        $constructors = AbstractPopulationHelper::getConstrutorArray($this->_maps, $entity, $row, $this->_db);
                
        if(count($constructors) == 0)
        {
            $class = new $className;            
        } else {
            $r = new ReflectionClass($className);
            $class = $r->newInstanceArgs($constructors);
        }

        $class->setId($row['id']);
        return $class;
    }
    
    public function save(IDivineEntity $entity)
    {
        $queries = AbstractPopulationHelper::generateUpdateSaveQuery($this->_maps, $entity, $entity->getId(), $this->_db);
        
       // if($queries['TYPE'] == AbstractPopulationHelper::QUERY_TYPE_CREATE)
       // {
            unset($queries['TYPE']);
            $idMap = [];
            foreach($queries as $index => $query)
            {                                
                $runQuery = true;
                if (preg_match_all('/'.preg_quote('%').'(.*?)'.preg_quote('%').'/s', $query, $matches)) {
                    foreach($matches[1] as $index_ref)
                    {
                        if($index_ref != 'MAIN_QUERY_ID')
                        {
                            $index_id = str_replace('INDEX_REF_', '', $index_ref);
                            $query = str_replace('%INDEX_REF_' . $index_id . '%', $idMap['INDEX_REF_' . $index_id], $query);
                        } else {
                            $runQuery = false;
                        }
                    }
                }

                if($runQuery)
                {
                    $statement = $this->_db->prepare($query);
                    $statement->execute();
                    $refIndex = $index+1;
                    $idMap['INDEX_REF_' . $refIndex] = $this->_db->lastInsertId();
                    unset($queries[$index]);
                } else {
                    //update query so that other references are resolved.
                    $queries[$index] = $query;
                }
            }
            
            //at this point we have queries left that depend on the main query id
            foreach($queries as $query)
            {
                $query = str_replace('%MAIN_QUERY_ID%', end($idMap), $query);
                $statement = $this->_db->prepare($query);
                $statement->execute();
            }
        //}
        
        echo '<pre>';
        print_r($queries);
        echo '</pre>';
    }
    
    //TODO: Implement
    public function remove(IDivineEntity $entity) {
        ;
    }
}
