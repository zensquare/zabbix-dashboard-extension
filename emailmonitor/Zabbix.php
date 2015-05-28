<?php

class Zabbix {
    
    protected $host;
    protected $port=10051;
    protected $timeout = 15;
    protected $hostname = "";
    
    public function __construct($params=null){
        if(!empty($params)){
            if(is_string($params)){
                $this->host = $params;
            } else {
                if(isset($params['host'])){
                    $this->host = $params['host'];
                }
                if(isset($params['port'])){
                    $this->port = $params['port'];
                }
                if(isset($params['hostname'])){
                    $this->hostname = $params['hostname'];
                }
            }
        }
    }
    
    public function send($data){
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if($socket == null) { return false; }
        
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $this->timeout, 'usec' => 0));
        
        if(!socket_connect($socket, $this->host, $this->port)){
            return false;
        }
        
        $packed = json_encode($data);
        
        echo $packed;
        
        //Write Header
        socket_write($socket, "ZBXD\x1");
        
        //Write Data length
        $length = strlen($packed);
        socket_write($socket,pack("V*",$length,$length >> 32));
        
        socket_write($socket, $packed);
        
        $return_header = socket_read($socket, 5);
        $return_length = unpack("V*",socket_read($socket, 8));
        
        $return_length = $return_length[1] + ($return_length[2] << 32);
        
        
        $return_data = socket_read($socket, $return_length);
        
        $return_data =  json_decode($return_data);
        
        socket_close($socket);
     
        return $return_data;
    }
    
    public function check($key,$value,$hostname=null){
        if(empty($hostname)){
            $hostname = $this->hostname;
        }
        
        return $this->send(
            array(
                'request'=>'agent data',
                'data'=>array(
                    array(
                        "host"=>$hostname,
                        'key'=>$key,
                        'value'=>$value,
                        'clock'=>time()
                    )
                ),
                'clock'=>time()
            )
        );
        
    }
    
}