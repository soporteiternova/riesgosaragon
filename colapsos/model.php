<?php
/**
 * GeoRiesgos Aragón - ITERNOVA <info@iternova.net>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Vehicle data model
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package busstop
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\colapsos;

class model extends \georiesgosaragon\common\model {
    public $_database_collection = 'colapsos';
    public $objectid = -1;
    public $id_colapso = -1;
    public $code_unio = '';
    public $code_geom = -1;
    public $code_cotr = '';
    public $code_eda1 = -1;
    public $name_eda1 = '';
    public $code_eda2 = -1;
    public $name = '';
    public $descripcion = '';
    public $dolinas = '';
    public $mov_tierra = '';
    public $inundacion = '';
    public $geo145_ = -1;
    public $color = -1;
    public $geo179_ = -1;
    public $geo180_ = -1;
    public $geologia_ = -1;
    public $geologia_i = -1;
    public $denominaci = '';
    public $cruces = -1;
    public $riesgo = '';
    public $fuente = '';
    public $accidente = '';
    public $fid_t0212_ = -1;
    public $fuente_1 = '';
    public $accident_1 = '';
    public $revsn_2013 = -1;


    public $recordtime = '';

    /**
     * Updates a glide object from open data api, and creates it if doesn't exist
     *
     * @param $api_object
     *
     * @return bool
     */
    public function update_from_api( $api_object ) {
        $this->_id = null;
        $ret = false;

        if( isset( $api_object->properties->objectid)) {
            $array_criteria[] = [ 'objectid', 'eq', $api_object->properties->objectid, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->_id = $saved_obj->_id;
            } else {
                $this->recordtime = date( 'Y-m-d H:i:s' );
            }

            $array_equivalence = [
                'objectid' => 'objectid',
                'id_colapso' => 'fid_mcolap',
                'code_unio' => 'code_unio',
                'code_geom' => 'code_geom',
                'code_cotr' => 'code_cotr',
                'code_eda1' => 'code_eda1',
                'name_eda1' => 'name_eda1',
                'code_eda2' => 'code_eda2',
                'name' => 'name_eda2',
                'descripcion' => 'desc_unit',
                'dolinas' => 'dolinas',
                'mov_tierra' => 'mov_tierra',
                'inundacion' => 'inundacion',
                'geo145_' => 'geo145_',
                'color' => 'color',
                'geo179_' => 'geo179_',
                'geo180_' => 'geo180_',
                'geologia_' => 'geologia_',
                'geologia_i' => 'geologia_i',
                'denominaci' => 'denominaci',
                'cruces' => 'cruces',
                'riesgo' => 'riesgo',
                'fuente' => 'fuente',
                'accidente' => 'accidente',
                'fid_t0212_' => 'fid_t0212_',
                'fuente_1' => 'fuente_1',
                'accident_1' => 'accident_1',
                'revsn_2013' => 'revsn_2013',
            ];

            foreach ( $array_equivalence as $attr => $tag ) {
                $this->{$attr} = $api_object->properties->$tag;
            }

            $api_geometry = $api_object->geometry->coordinates;
            $count_array = 0;
            while( is_array( $api_geometry ) ){
                $api_geometry = reset( $api_geometry);
                $count_array++;
            }

            $api_geometry = $api_object->geometry->coordinates;
            $count_array-=2;
            for( $x = 0; $x<$count_array; $x++){
                $api_geometry = reset( $api_geometry );
            }
            foreach( $api_geometry as $key => $coords ){
                $coords = \georiesgosaragon\common\utils::OSGB36ToWGS84( $coords[ 1 ], $coords[ 0 ], 30 );
                $api_geometry[$key] = [$coords[1], $coords[0]];
            }

            for ( $x = 0; $x < $count_array; $x++ ) {
                $api_geometry = [$api_geometry];
            }

            $api_object->geometry->coordinates = $api_geometry;
            $this->geometry = json_encode([$api_object->geometry]);

            $ret = $this->store();
        }

        return $ret;
    }

    public function get_json( $date_min, $date_max ) {
        ini_set('memory_limit', '2G');
        $ret = [ 'type' => 'FeatureCollection', 'features' => [] ];
        $array_opts[] = [ 'recordtime', 'gte', $date_min, 'MongoDate' ];
        $array_opts[] = [ 'recordtime', 'lte', $date_max, 'MongoDate' ];

        $array_glides = $this->get_all( $array_opts, [], 0, 2000 );

        if( !empty( $array_glides ) ) {
            foreach( $array_glides as $glide ) {
                $ret['features'][] = ['type' => 'Feature', 'properties' => [ 'description' => $glide->descripcion ], 'geometry' => json_decode($glide->geometry) ];
            }
        }
        return json_encode( $ret );
    }

    /**
     * Sets collection indexes
     * @return bool Resultado de la operacion
     * @throws \Exception
     */
    protected function ensureIndex() {
        $array_indexes = [
            [ 'objectid' => 1 ],
            [ 'recortdime' => 1 ],
            [ 'vertex' => '2dsphere' ],
        ];
        foreach ( $array_indexes as $index ) {
            $this->_database_controller->ensureIndex( $this->_database_collection, $index );
        }
        return true;
    }

    /**
     * Cofieds object to utf8/iso8859-1
     *
     * @param boolean $to_utf8 if true, converts to utf8, if false, converts to iso8859-1
     *
     * @return void
     */
    public function object_encode_data( $to_utf8 = false ) {
        $callback_function = \georiesgosaragon\common\utils::class . ( $to_utf8 ? '::detect_utf8' : '::detect_iso8859_1' );

        // Dates (format \MongoDate en UTC+0)
        $array_fields_datetime = [ 'recordtime', 'updated_at', 'created_at' ];
        foreach ( $array_fields_datetime as $key ) {
            $this->{$key} = \georiesgosaragon\common\databasemongo::datetime_mongodate( $this->{$key}, $to_utf8, false );
        }

        // Common attributes: integer
        $array_integer = [ 'objectid', 'id_colapso', 'code_geom', 'code_eda1', 'code_eda2', 'geo145_', 'color', 'geo179_', 'geo180_', 'geologia_', 'geologia_i', 'cruces', 'fid_t0212_', 'revsn_2013' ];
        foreach ( $array_integer as $key ) {
            $this->{$key} = (integer) $this->{$key};
        }

        // Common attributes: string
        $array_string = [ 'descripcion', 'name', 'id_colapso', 'code_unio', 'code_cotr', 'name_eda1', 'dolinas', 'mov_tierra', 'inundacion', 'denominaci', 'riesgo', 'fuente', 'accidente', 'fuente_1', 'accident_1' ];
        foreach ( $array_string as $key ) {
            $this->{$key} = (string) \call_user_func( $callback_function, $this->{$key} );
        }
        if ( !empty( $this->lat_lng ) ) {
            $this->lat_lng = [ (float) $this->lat_lng[ 0 ], (float) $this->lat_lng[ 1 ] ];
        }

        // Common attributes: booleans
        $array_boolean = [ 'active' ];
        foreach ( $array_boolean as $key ) {
            $this->{$key} = (boolean) $this->{$key};
        }
    }

    public function get_feature_array(){
        $coordinates = json_decode( $this->geometry );
        return ['type' => 'Feature', 'properties' => [ 'description' => $this->descripcion, 'color' => 'red' ], 'geometry' => reset( $coordinates )];
    }
}
