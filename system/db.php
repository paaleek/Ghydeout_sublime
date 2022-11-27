<?php 
class DB
{	public $server='localhost';
    public $user;
    public $password;
    public $dbname;

    private $conn;
    private $query;
	
	function __construct($server,$user,$password,$dbname)
	{
		$this->server = $server;
        $this->user= $user;
        $this->password = $password;
        $this->dbname = $dbname;
        

        $this->conn = new mysqli($this->server, $this->user, $this->password,$this->dbname);
        $this->conn->set_charset("utf8");

        if ($this->conn->connect_error)
        {
            die("Connection failed: " . $this->conn->connect_error());
        }

	}
	public function query($sql)
    {
        $this->query = $sql;
        $result = $this->conn->query($sql);


        if(is_object($result))
        {
            return $result->fetch_all(MYSQLI_ASSOC); 
        }
        else
        {
            return $result;
        }

    }
    public function select($table, $colums='*',$where='',$limit='')
    {
    	$sql = 'SELECT '.$colums.' FROM '.$table;
    	if(!empty($where))
    	{
    		$sql = $sql.' WHERE '.$where;
    	}
        if(!empty($limit))
        {
            $sql = $sql.' LIMIT'.$limit;
        }
    	return $this->query($sql);
    }
    public function count($table,$condition='')
    {
        $sql = 'SELECT COUNT(*) AS cnt FROM '.$table;
        

        if(!empty($condition))
        {
            $sql = $sql.' WHERE'.$condition;
        }
        return $this->query($sql)[0]['cnt'];
    }

    public function update($table, $values,$where='')
    {
    	$set='';

        foreach ($values as $key => $value) 
        {
            $set = $set.(empty($set)? ' ': ', ').$key. ' = "'.$this->escape_string($value).'"';
        }
        $sql = 'UPDATE '.$table.' SET '.$set.' WHERE '.$where;  
        return  $this->query($sql);
    }
    public function insert($table,$values)
    {
    	$set='';
        $hodnoty='';

        foreach ($values as $key => $value) 
        {
            $set = $set.(empty($set)? ' ': ', ').$key;
            $hodnoty = $hodnoty.(empty($hodnoty)? ' ': ', ').'"'.$this->conn->escape_string($value).'"';
        }
        $sql = 'INSERT INTO '.$table.' ('.$set.') VALUES ('.$hodnoty.')';

        return  $this->query($sql);
    }
    public function escape_string($value)
    {
        return $this->conn->escape_string($value);
    }
    public function getSQL()
    {
       return $this->query; 
    }
}
 ?>