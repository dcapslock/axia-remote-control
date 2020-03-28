<?php

class Studio {
    const FADER_GET_ACTIONS = array(    'channelstate', 'sourceprofile', 'sourceprofiles', 'fadergain', 'channelbus');
    const FADER_SET_ACTIONS = array(    'channelstate', 'sourceprofile', 'fadergain', 
                                        'channelbus_pgm1', 'channelbus_pgm2', 'channelbus_pgm4', 'channelbus_pgm4', 
                                        'channelbus_prev' );

    const CONSOLE_GET_ACTIONS = array(  'showprofile', 'showprofiles');
    const CONSOLE_SET_ACTIONS = array(  'showprofile' );

    const VMIX_GET_ACTIONS = array(  'vmixstate', 'vmixgain' );
    const VMIX_SET_ACTIONS = array(  'vmixstate', 'vmixgain' );

    const LIVEWIRE_CONTROL_CLI = __DIR__ . '\Livewire-Control-CLI.exe';

    function __construct($vars) {
        foreach ($vars as $key => $value) {
            $this->{$key} = $value;
        }
        
        $this->response = array();
    }

    function getConsole() {
        $this->Console("get", SELF::CONSOLE_GET_ACTIONS );
    }

    function setConsole() {
        $this->Console("set", SELF::CONSOLE_SET_ACTIONS );
    }

    function getFader() {
        $this->Console("get", SELF::FADER_GET_ACTIONS, [ "--fadernum {$this->fader}" ] );
    }

    function setFader() {
        $this->Console("set", SELF::FADER_SET_ACTIONS, [ "--fadernum {$this->fader}" ] );
    }

    function getVmix() {
        $this->Console("get", SELF::VMIX_GET_ACTIONS, [ "--vmix_num {$this->vmix}", "--vmix_chnum {$this->chnum}" ] );
    }

    function setVmix() {
        $this->Console("set", SELF::VMIX_SET_ACTIONS, [ "--vmix_num {$this->vmix}", "--vmix_chnum {$this->chnum}" ] );
    }

    function Console($method, $allowedActions, $args = []) {
        if (!in_array($this->action, $allowedActions)) {
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
            echo ("Unknown Action: $this->action");
            die();
        }

        @$data = ($this->data) ? " {$this->data}" : "";
        $args[] = "--{$method}_{$this->action}" . $data;
        $args[] = "$this->console";

        $commandLine = "\"" . SELF::LIVEWIRE_CONTROL_CLI . "\"" . " " . implode(' ', $args);
        exec($commandLine, $result, $error);
        
        $this->response['error'] = $error;

        if ($error == 0) {
            foreach ($result as $line) {
                preg_match('/([^=\:]*)(?:[=:])?(.*)?/', $line, $output);            
                $this->response[$this->action][$output[1]][] = ($output[2]) ? $output[2] : true;
            }
        }

        header('Content-type: application/json');
        echo json_encode($this->response);
    }
}



