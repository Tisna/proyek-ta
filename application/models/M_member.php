<?php
/**
 * Member model
 */
class M_member extends CI_Model
{
  var $table = "member";
  var $select_column = array("id", "full_name", "username", "email", "avatar", "date_created");
  var $order_column = array(null, "full_name", "username", null, null, null);
  var $column_order = array("id", "full_name","email", "username",null,  "avatar",null,"role", "date_created"); //set column field database for datatable orderable
  var $column_search = array('full_name','email','username'); //set column field database for datatable searchable just firstname , lastname , address are searchable
  var $order = array('id' => 'desc'); // default order 

  public function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  /**
   * 
   */

  private function _get_datatables_query()
  {
    
    $this->db->from($this->table);

    $i = 0;
  
    foreach ($this->column_search as $item) // loop column 
    {
      if($_POST['search']['value']) // if datatable send POST for search
      {
        
        if($i===0) // first loop
        {
          $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
          $this->db->like($item, $_POST['search']['value']);
        }
        else
        {
          $this->db->or_like($item, $_POST['search']['value']);
        }

        if(count($this->column_search) - 1 == $i) //last loop
          $this->db->group_end(); //close bracket
      }
      $i++;
    }
    
    if(isset($_POST['order'])) // here order processing
    {
      $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
    } 
    else if(isset($this->order))
    {
      $order = $this->order;
      $this->db->order_by(key($order), $order[key($order)]);
    }
  }

  function get_datatables()
  {
    $this->_get_datatables_query();
    if($_POST['length'] != -1)
    $this->db->limit($_POST['length'], $_POST['start']);
    $query = $this->db->get();
    return $query->result();
  }

  /**
   * dataTables function
   * @return [type] [description]
   */
  public function make_query()
  {
    $this->db->select($this->select_column);
    $this->db->from($this->table);

    if (isset($_POST['search']['value'])) {
      $this->db->like('full_name', $_POST['search']['value']);
      $this->db->or_like('username', $_POST['search']['value']);
    }

    if (isset($_POST['order'])) {
      $this->db->order_by($this->db->order_column[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
    }else {
      $this->db->order_by('id', 'DESC');
    }
  }

  public function make_datatables()
  {
    $this->make_query();
    if ($_POST["length"] != -1) {
      $this->db->limit($_POST["length"], $_POST["start"]);
    }
    $query = $this->db->get();
    return $query->result();
  }

  public function get_filtered_data()
  {
    $this->make_query();
    $query = $this->db->get();
    return $query->num_rows();
  }

  public function get_all_data()
  {
    $this->db->select('*');
    $this->db->from($this->table);
    return $this->db->count_all_results();
  }

  public function register()
  {
    $salt = $this->salt();
    $password = $this->makePassword($this->input->post('password'), $salt);

    $insertData = array(
      'full_name' => $this->input->post('full-name'),
      'email' =>$this->input->post('email'),
      'username' => $this->input->post('username'),
      'password' => $password,
      'salt' => $salt,
      'role' => 'member'
    );

    $this->db->insert('member', $insertData);
  }

  public function salt()
  {
    return password_hash("rasmuslerdorf", PASSWORD_DEFAULT);
  }

  public function makePassword($password = null, $salt = null)
  {
    if ($password & $salt) {
      return hash('sha512', $password.$salt);
    }
  }

  public function validate_username()
  {
    $username = $this->input->post('username');
    $sql = "SELECT * FROM member where username = ?";
    $query = $this->db->query($sql, array($username));
    return($query->num_rows() == 1) ? true: false;
  }

  public function fetchDataByUsername($username = null)
  {
    if ($username) {
      $sql = "SELECT salt FROM member WHERE username = ?";
      $query = $this->db->query($sql, array($username));
      $result = $query->row_array();

      return ($query->num_rows() == 1) ? $result : false;
      return $result;
    }
  }
  /**
   * login
   */
  public function login()
  {
    $username = $this->input->post('username');
    $password = $this->input->post('password');

    $userdata = $this->fetchDataByUsername($username);

    if ($userdata) {
      $password = $this->makePassword($password, $userdata['salt']);
      $sql = "SELECT * FROM member WHERE username = ? AND password = ?";
      $query = $this->db->query($sql, array($username, $password));
      $result = $query->row_array();

      return ($query->num_rows() == 1) ? $result : false;
      return $result;
    }else {
      return false;
    }
  }

  public function get_user_profile()
  {
    $username = $this->input->post('username');
    $password = $this->input->post('password');

    $userdata = $this->fetchDataByUsername($username);

    if ($userdata) {
      $password = $this->makePassword($password, $userdata['salt']);
      $sql = "SELECT * FROM member WHERE username = ? AND password = ?";
      $query = $this->db->query($sql, array($username, $password));
      $result = $query->row_array();

      return $result;
    }else {
      return false;
    }
  }

  public function fetch_data_member()
  {
      $sql = "SELECT * FROM member";
      $query = $this->db->query($sql);

      return $query->result_array();
  }

  /**
   * display member
   */

  public function get_profile($uname)
  {
    $query = $this->db->get_where('member', array('username' => $uname));
    if ($query->num_rows()) {
      return  $query->row_array();
      // return ($query->num_rows() == 1) ? $result['username'] : false;
    }else {
      show_404();
      exit;
    }
  }
}
