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
    public static function insert_record_parameters() {
        return new external_function_parameters (
            array (
                'table_name' => new external_value(PARAM_TEXT, 'Nome da tabela a serem inserido os dados.'),
                'enunciado' => new external_value(PARAM_TEXT, 'Enunciado da pergunta.'),            
                'alternativaa' => new external_value(PARAM_TEXT, 'Alternativa A.'),
                'alternativab' => new external_value(PARAM_TEXT, 'Alternativa B.'),
                'alternativac' => new external_value(PARAM_TEXT, 'Alternativa C.'),
                'alternativad' => new external_value(PARAM_TEXT, 'Alternativa D.'),
                'respostacerta' => new external_value(PARAM_TEXT, 'Resposta correta.'),
                'resposta' => new external_value(PARAM_TEXT, 'Resposta escolhida pelo usuário.'),
                'timestamp' => new external_value(PARAM_INT, 'Data e hora de quando foi a tentativa.'),
                'hash' => new external_value(PARAM_TEXT, 'Hash do usuário para o Moodle.'),
                'remar_resource_id' => new external_value(PARAM_INT, 'Json with the content.')
            )
        );
    }
 
    /**
     * The function itself
     * @return string welcome message
     */
    public static function insert_record($table_name, $enunciado, $alternativaa, $alternativab,
                                        $alternativac, $alternativad, $respostacerta, $resposta,
                                        $timestamp, $hash, $remar_resource_id) {
        global $DB;
        
        //Parameters validation
        $validated_params = self::validate_parameters(self::insert_record_parameters(),
            array(
                'table_name' => $table_name,
                'enunciado' => $enunciado,
                'alternativaa' => $alternativaa,
                'alternativab' => $alternativab,
                'alternativac' => $alternativac,
                'alternativad' => $alternativad,
                'respostacerta' => $respostacerta,
                'resposta' => $resposta,
                'timestamp' => $timestamp,
                'hash' => $hash,
                'remar_resource_id' => $remar_resource_id
            )
        );
        
        $data = new stdClass();
        $data->user_id = "";
        
        $user = $DB->get_record('remarmoodle_user', array('hash' => $validated_params['hash']));
        if ($user != null) {
            $user = $DB->get_record('user', array('username' => $user->moodle_username));
            if ($user != null) {
                $data->user_id = $user->id;
            }
        }
        
        $data->enunciado = $enunciado;
        $data->alternativaa = $alternativaa;
        $data->alternativaa = $alternativaa;
        $data->alternativab = $alternativab;
        $data->alternativac = $alternativac;
        $data->alternativad = $alternativad;
        $data->respostacerta = $respostacerta;
        $data->resposta = $resposta;
        $data->timestamp = $timestamp;
        $data->remar_resource_id = $remar_resource_id;
        
        $lastinsertid = $DB->insert_record('remarmoodle_'.$validated_params['table_name'], $data);
 
        $ret = array (
            'code' => $lastinsertid,
            'description' => ' OK!'
        );
        
        return array('json' => json_encode($ret));
    }
 
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function insert_record_returns() {
        return new external_single_structure(
            array (
                'json' => new external_value(PARAM_TEXT, 'Json de retorno.'),
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
                'json' => new external_value(PARAM_TEXT, 'Json describing the structure of the table to be created', VALUE_REQUIRED)
            )
        );
    }
 
    /**
     * The function itself
     * @return string welcome message
     */
    public static function create_table($json) {
        global $DB;
        $dbman = $DB->get_manager();
        try {
            $validated_params = self::validate_parameters(self::create_table_parameters(), array('json' => $json));

            $keys = array();

            $new_json = json_decode($validated_params['json']);
            $table_name = "remarmoodle_".$new_json->table_name;
            
            if(!$dbman->table_exists($table_name) ) {
                $table = new xmldb_table($table_name);
                
                $idField = new xmldb_field("id");
                $idField->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
                
                $userIdField = new xmldb_field("user_id");
                $userIdField->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

                $remarResourceId = new xmldb_field("remar_resource_id");
                $remarResourceId->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

                $key1 = new xmldb_key('primary');
                $key1->set_attributes(XMLDB_KEY_PRIMARY, array('id'), null, null);

                $table->addField($idField);
                $table->addField($cmField);
                $table->addField($userIdField);
                $table->addField($remarResourceId);

                $table->addKey($key1);

                foreach($new_json->structure as $raw_field) {
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
                                        return array('json' => json_encode($ret));
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
                                        return array('json' => json_encode($ret));
                                    }
                                }
                                break;

                            case 'CHAR':
                                $type = XMLDB_TYPE_CHAR;
                                if (!is_null($raw_field->length) && !empty($raw_field->length) && is_numeric($raw_field->length) && $raw_field->length > 0) {
                                    $length = $raw_field->length;
                                } else {
                                    $ret = getError(3, $raw_field->name);
                                    return array('json' => json_encode($ret));
                                }
                                if (!is_null($raw_field->notnull) && !empty($raw_field->notnull) && is_bool($raw_field->notnull)) {
                                    $notnull = $raw_field->notnull;
                                }
                                if (!is_null($raw_field->default) && !empty($raw_field->default)) {
                                    if (strlen($raw_field->default) != 1) {
                                        $default = $raw_field->default;
                                    } else {
                                        $ret = getError(4, $raw_field->type);
                                        return array('json' => json_encode($ret));
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
                                    return array('json' => json_encode($ret));
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
                                        return array('json' => json_encode($ret));
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
                                    return array('json' => json_encode($ret));
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
                                        return array('json' => json_encode($ret));
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
                                        return array('json' => json_encode($ret));
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
                                        return array('json' => json_encode($ret));
                                    }
                                }
                                break;

                            default:
                                $ret = getError(1, $raw_field->type);
                                return array('json' => json_encode($ret));
                                break;
                        }

                        $field->set_attributes($type, $length, $unsigned, $notnull, $sequence, $default);

                        $table->addField($field);
                    }
                }

                $status = $dbman->create_table($table);
                
                $ret = array (
                    'message' => 'Success',
                    'description' => '-SUCCESS!!-'
                );
                
                return array('json' => json_encode($ret));
            }
            else {
                $ret = array (
                    'message' => 'Error',
                    'description' => "There is a table called \'".$table_name."\' already."
                );

                return array('json' => json_encode($ret));
            }
        }
        catch(Exception $e) {
            $ret = array (
                'message' => 'Error',
                'description' => $e->getMessage()
            );
            
            return array('json' => json_encode($ret));
        }
    }
 
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function create_table_returns() {
        return new external_single_structure(
            array (
                'json' => new external_value(PARAM_TEXT, 'Json de retonro')
            )
        );
    }






    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function token_verifier_parameters() {
        return new external_function_parameters (
            array (
                'hash' => new external_value(PARAM_TEXT, 'User hash.')
            )
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function token_verifier($hash) {
        global $DB;

        $validated_params = self::validate_parameters(self::token_verifier_parameters(), array('hash' => $hash));

        $user = $DB->get_record('remarmoodle_user', array('hash' => $validated_params['hash']));
        
        $ret = array (
            'username' => $user->moodle_username,
            'description' => 'Moodle username'
        );

        return array('json' => json_encode($ret));
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function token_verifier_returns() {
        return new external_single_structure(
            array (
                'json' => new external_value(PARAM_TEXT, 'Json de retorno.')
            )
        );
    }

}
