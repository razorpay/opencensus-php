<?php

namespace RZP\Modules\Acs;

use http\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;

class ASVEntityMapper {
    public static function OverwriteWithAsvEntities($entityClass, $primaryKeyField, $entitiesFromAPI, $entitiesFromASV) {
        $asvEntityMap = [];
        foreach ($entitiesFromASV as $entityFromASV) {
            $asvEntityMap[$entityFromASV[$primaryKeyField]] = $entityFromASV;
        }
        $mergedEntities = [];
        foreach ($entitiesFromAPI as $entityFromAPI) {
            if (array_key_exists($entityFromAPI[$primaryKeyField], $asvEntityMap) === false) {
                continue;
            }
            $entityFromASV = $asvEntityMap[$entityFromAPI[$primaryKeyField]];
            array_push($mergedEntities, self::OverwriteWithAsvEntity($entityFromAPI, $entityFromASV, $entityClass));
        }
        return new PublicCollection($mergedEntities);
    }

    public static function OverwriteWithAsvEntity($entityFromAPI, $entityFromASV, $entityClass) {
        return ASVEntityMapper::MapDataArrayToEntity(array_merge($entityFromAPI,$entityFromASV),$entityClass);
    }

    public static function MapProtoObjectToEntity($protoObject, $entityClass) {
        $protoString = $protoObject->serializeToJsonString();
        $protoAsArray = json_decode($protoString, true);
        $protoAsArray = ASVEntityMapper::doSnakeCase($protoAsArray);
        return ASVEntityMapper::MapDataArrayToEntity($protoAsArray, $entityClass);
    }

    public static function MapProtoObjectIteratorToEntityCollection($protoObjectIterator, $entityClass): PublicCollection
    {
        $entities = [];
        foreach($protoObjectIterator as $protoObject) {
            $entities[] = ASVEntityMapper::MapProtoObjectToEntity($protoObject, $entityClass);
        }

        return new PublicCollection($entities);
    }

    public static function MapDataArrayToEntity($dataArray, $entityClass) {
        try {
            $entityClass::unguard();
            $mappedEntity = new $entityClass($dataArray);
        } catch(\Error $e) {
            throw new RuntimeException('Could not map proto response to entity');
        } finally {
            $entityClass::reguard();
        }
        return $mappedEntity;
    }

    public static function EntitiesToArrayWithRawValues($entities) : array{

        $array = [];
        foreach($entities as $entity){
            $array[] = ASVEntityMapper::EntityToArrayWithRawValues($entity);
        }

        return $array;
    }

    public static function EntityToArrayWithRawValues($entity) : array{
        if(method_exists($entity, 'toArrayWithRawValuesForAccountService')){
            return $entity->toArrayWithRawValuesForAccountService();
        }

        return $entity->toArray();
    }

    private static function doSnakeCase(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $key = strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $key));

            $result[$key] = $value;
        }

        return $result;
    }
}
