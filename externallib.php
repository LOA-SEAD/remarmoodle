<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once($CFG->libdir . "/externallib.php");
require_once(dirname(__FILE__).'/lib.php');

class mod_remarmoodle_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function quiforca_update_parameters() {
        /*return new external_function_parameters(
            array('PARAM1' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );*/
        
        return new external_function_parameters (
            array ( 
                'params' => new external_single_structure (
                    array(
                        'table_name' => new external_value(PARAM_TEXT, 'Table name where the data is going to be saved.', VALUE_REQUIRED),
                        'json' => new external_value(PARAM_TEXT, 'Json with the content', VALUE_REQUIRED)
                    )
                )
            )
        );
    }
 
    /**
     * The function itself
     * @return string welcome message
     */
    public static function quiforca_update($params) {
        global $DB;
        
        //Parameters validation
        $validated_params = self::validate_parameters(self::quiforca_update_parameters(), array('params' => $params));
 
        $lastinsertid = $DB->insert_record('remarmoodle_quiforca', $params);
 
        $ret = array (
            'code' => $lastinsertid,
            'description' => 'ID do último item inserido no banco'
        );
        
        return $ret;
    }
 
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function quiforca_update_returns() {
        /*return new external_single_structure (
            array(
                'userid' => new external_value(PARAM_INT, 'ID do usuário'),
                'cm' => new external_value(PARAM_INT, 'ID do módulo do curso (course module - cm)'),
                'instance_id' => new external_value(PARAM_INT, 'ID da instância do game'),
                'dica' => new external_value(PARAM_TEXT, 'Dica para acertar a palavra'),
                'palavra' => new external_value(PARAM_TEXT, 'Palavra que é a resposta'),
                'contribuicao' => new external_value(PARAM_TEXT, 'Pessoa que contribuiu para a criação desta palavra'),
                'letra_escolhida' => new external_value(PARAM_TEXT, 'Armazena a letra escolhida (jogada)'),
                'timestamp' => new external_value(PARAM_ALPHANUMEXT, 'Timestamp de quando foi feita a jogada')
            )
        );*/
        return new external_single_structure(
            array (
                'code' => new external_value(PARAM_INT, 'Código do último item inserido no banco ou do erro causado.'),
                'description' => new external_value(PARAM_TEXT, 'Descrição')
            )
        );
    }
    
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_table_parameters() {
        return new external_function_parameters (
            array ( 
                'params' => new external_single_structure (
                    array(
                        'table_name' => new external_value(PARAM_TEXT, 'Table name', VALUE_REQUIRED),
                        'structure' => new external_value(PARAM_RAW, 'Structure table string', VALUE_REQUIRED)
                    )
                )
            )
        );
    }
 
    /**
     * The function itself
     * @return string welcome message
     */
    public static function create_table($params) {
        global $DB;
        $dbman = $DB->get_manager();
        try {
            $validated_params = self::validate_parameters(self::create_table_parameters(), array('params' => $params));

            $keys = array();

            $json = json_decode($validated_params['params']['structure']);
            $table_name = "".$validated_params['params']['table_name'];

            if(!$dbman->table_exists($table_name) ) {
                $table = new xmldb_table($table_name);

                $idField = new xmldb_field("id");
                $idField->set_attributes(XMLDB_TYPE_INTEGER, 10, false, true, true, false);

                $cmField = new xmldb_field("cm");
                $cmField->set_attributes(XMLDB_TYPE_INTEGER, 10, false, true, true, false);

                $userIdField = new xmldb_field("user_id");
                $userIdField->set_attributes(XMLDB_TYPE_INTEGER, 10, false, true, true, false);

                $idKey = new xmldb_key('primary_key');
                $idKey->set_attributes(XMLDB_KEY_PRIMARY, $idKey);

                $table->addKey($idKey);

                $table->addField($idField);
                $table->addField($cmField);
                $table->addField($userIdField);

                foreach($json as $raw_field) {
                    if ($raw_field->name != "id" && $raw_field->name != "cm" && $raw_field->name != "user_id") {
                        $type = null;
                        $length = null;
                        $notnull = null;
                        $unsigned = null;
                        $sequence = null;

                        $field = new xmldb_field($raw_field->name);

                        switch (strtoupper($raw_field->type)) {
                            case 'BINARY':
                                $type = XMLDB_TYPE_BINARY;
                                if (!is_null($raw_field->length) && !empty($raw_field->length) && is_string($raw_field->length)) {
                                    if (strcmp(strtoupper($raw_field->length), strtoupper('small')) ||
                                        strcmp(strtoupper($raw_field->length), strtoupper('medium')) ||
                                        strcmp(strtoupper($raw_field->length), strtoupper('big'))
                                    ) {
                                        $length = $raw_field->length;
                                    } else {
                                        $ret = getError(2, $raw_field->name);
                                        return $ret;
                                    }
                                }
                                if (!is_null($raw_field->notnull) && !empty($raw_field->notnull) && is_bool($raw_field->notnull)) {
                                    $notnull = $raw_field->notnull;
                                }
                                if (!is_null($raw_field->default) && !empty($raw_field->default)) {
                                    if (is_bool($raw_field->default)) {
                                        $default = $raw_field->default;
                                    } else {
                                        $ret = getError(4, $raw_field->type);
                                        return $ret;
                                    }
                                }
                                break;

                            case 'CHAR':
                                $type = XMLDB_TYPE_CHAR;
                                if (!is_null($raw_field->length) && !empty($raw_field->length) && is_numeric($raw_field->length) && $raw_field->length > 0) {
                                    $length = $raw_field->length;
                                } else {
                                    $ret = getError(3, $raw_field->name);
                                    return $ret;
                                }
                                if (!is_null($raw_field->notnull) && !empty($raw_field->notnull) && is_bool($raw_field->notnull)) {
                                    $notnull = $raw_field->notnull;
                                }
                                if (!is_null($raw_field->default) && !empty($raw_field->default)) {
                                    if (strlen($raw_field->default) != 1) {
                                        $default = $raw_field->default;
                                    } else {
                                        $ret = getError(4, $raw_field->type);
                                        return $ret;
                                    }
                                }
                                break;

                            case 'INTEGER':
                                $type = XMLDB_TYPE_INTEGER;
                                if (!is_null($raw_field->unsigned) && !empty($raw_field->unsigned) && is_bool($raw_field->unsigned)) {
                                    $unsigned = $raw_field->unsigned;
                                }
                                if (!is_null($raw_field->length) && !empty($raw_field->length) && is_numeric($raw_field->length) && $raw_field->length > 0) {
                                    $length = $raw_field->length;
                                } else {
                                    $ret = getError(3, $raw_field->name);
                                    return $ret;
                                }
                                if (!is_null($raw_field->notnull) && !empty($raw_field->notnull) && is_bool($raw_field->notnull)) {
                                    $notnull = $raw_field->notnull;
                                }
                                if (!is_null($raw_field->sequence) && !empty($raw_field->sequence) && is_bool($raw_field->sequence)) {
                                    $sequence = $raw_field->sequence;
                                }
                                if (!is_null($raw_field->default) && !empty($raw_field->default)) {
                                    if (is_string($raw_field->default)) {
                                        $default = $raw_field->default;
                                    } else {
                                        $ret = getError(4, $raw_field->type);
                                        return $ret;
                                    }
                                }
                                break;

                            case 'NUMBER':
                                $type = XMLDB_TYPE_NUMBER;
                                if (!is_null($raw_field->unsigned) && !empty($raw_field->unsigned) && is_bool($raw_field->unsigned)) {
                                    $unsigned = $raw_field->unsigned;
                                }
                                if (!is_null($raw_field->length) && !empty($raw_field->length) && is_numeric($raw_field->length) && $raw_field->length > 0) {
                                    $length = $raw_field->length;
                                } else {
                                    $ret = getError(3, $raw_field->name);
                                    return $ret;
                                }
                                if (!is_null($raw_field->notnull) && !empty($raw_field->notnull) && is_bool($raw_field->notnull)) {
                                    $notnull = $raw_field->notnull;
                                }
                                if (!is_null($raw_field->sequence) && !empty($raw_field->sequence) && is_bool($raw_field->sequence)) {
                                    $sequence = $raw_field->sequence;
                                }
                                if (!is_null($raw_field->default) && !empty($raw_field->default)) {
                                    if (is_numeric($raw_field->default)) {
                                        $default = $raw_field->default;
                                    } else {
                                        $ret = getError(4, $raw_field->type);
                                        return $ret;
                                    }
                                }
                                break;

                            case 'TEXT':
                                $type = XMLDB_TYPE_TEXT;
                                if (!is_null($raw_field->length) && !empty($raw_field->length) && is_string($raw_field->length)) {
                                    if (strcmp(strtoupper($raw_field->length), strtoupper('small')) ||
                                        strcmp(strtoupper($raw_field->length), strtoupper('medium')) ||
                                        strcmp(strtoupper($raw_field->length), strtoupper('big'))
                                    ) {
                                        $length = $raw_field->length;
                                    } else {
                                        $ret = getError(2, $raw_field->name);
                                        return $ret;
                                    }
                                }
                                if (!is_null($raw_field->notnull) && !empty($raw_field->notnull) && is_bool($raw_field->notnull)) {
                                    $notnull = $raw_field->notnull;
                                }
                                if (!is_null($raw_field->default) && !empty($raw_field->default)) {
                                    if (is_string($raw_field->default)) {
                                        $default = $raw_field->default;
                                    } else {
                                        $ret = getError(4, $raw_field->type);
                                        return $ret;
                                    }
                                }
                                break;

                            default:
                                $ret = getError(1, $raw_field->type);
                                return $ret;
                                break;
                        }

                        $field->set_attributes($type, $length, $unsigned, $notnull, $sequence, $default);

                        $key = null;

                        if (!is_null($raw_field->primary) && !empty($raw_field->primary) && is_bool($raw_field->primary)) {
                            $keys[] = $raw_field->name;
                        }

                        $table->addField($field);
                    }
                }

                if(!empty($keys)) {
                    $key = new xmldb_key('primary_key');
                    $key->set_attributes(XMLDB_KEY_PRIMARY, $keys);

                    $table->addKey($key);
                    $status = $dbman->create_table($table);
                }
                else {
                    $ret = getError(5);
                    return $ret;
                }

                $ret = array (
                    'message' => 'Success',
                    'description' => 'Table \''.$table_name.'\' was successfully created.'
                );
            }

            $ret = getError(6);
            return $ret;
        }
        catch(Exception $e) {
            $ret = array (
                'message' => 'Error',
                'description' => $e->getMessage()
            );
            
            return $ret;
        }
    }
 
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function create_table_returns() {
        return new external_single_structure(
            array (
                'message' => new external_value(PARAM_TEXT, 'Message'),
                'description' => new external_value(PARAM_TEXT, 'Description')
            )
        );
    }
}