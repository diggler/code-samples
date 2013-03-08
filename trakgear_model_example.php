<?php
/*file:equipment_model.php
 *location : /application/models/equipment_model.php
 *usage: model for equipment tables
 *overview: this is the main model for handling all gets, sets and updates
 *for the equipment within the db.  
 *updated: 3/01/2013 -- JA
 *Copyright Sportech Labs LLC 2012
 */
class Equipment_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
    /**
	* Create a neq equipment item in the db
	*
	* @access	public
	* @param	int sport_id, string equipmen_tname, string description, int poid, string size_type
	* @return	int equipment_id
	*/
    function create_equipment($sport_id,$equipment_name,$description,$poid,$size_type)
    {
		$data = array(
			'sportid'=>$sport_id,
			'equipmentname'=>$equipment_name,
			'description'=>$description,
			'poid'=>$poid,
			'size_type'=>$size_type
		);

		
		$this->db->insert('equipment',$data);
		return $this->db->insert_id();
	}
    /**
	* Create a new item in the db
	*
	* @access	public
	* @param	int quantity, string size, string item_name, string comment, string barcode, float cost, int po_num
	* @return	int item_id
	*/
    function create_item($quantity,$size,$item_name,$comment,$barcode,$cost,$po_num)
    {
		$data = array(
			'quantity'=>$quantity,
			'size'=>$size,
			'itemname'=>$item_name,
			'comment' => $comment,
			'barcode' => $barcode,
			'cost' => $cost,
			'po_num' => $po_num,
			'assigned' => $quantity

			);
		$this->db->insert('equipmentitem',$data);
		return $this->db->insert_id();
	}
	/**
	* Get the equipnent id by name and school
	*
	* @access	public
	* @param	string equipment_name, int school_id
	* @return	return equipment_id
	*/
	function get_equipment_id($equipment_name,$school_id)
	{
		$this->db->select('equipmentid, equipmentname, school_id')
				 ->from('equipment')
				 ->join('equipment_school', 'equipment_school.equipment_id = equipment.equipmentid','left')
				 ->where('school_id',$school_id)
				 ->where('equipmentname',$equipment_name);
		$query = $this->db->get();
		foreach($query->result() as $row)
			return $row->equipmentid;	
			
	}
	/**
	* Get all equipment and groups in a joined array
	*
	* @access	public
	* @param	int school_id
	* @return	array equipment and items
	*/
	function get_equipment_and_items($school_id)
	{
		$user_id = $this->ion_auth->user()->row()->id;
		$this->db->select('*')
				 ->from('equipment')
				 ->join('equipment_school', 'equipment_school.equipment_id = equipment.equipmentid','left')
				  ->join('item_equipment_ids', 'equipment.equipmentid = item_equipment_ids.equipment_id','left')
				 ->join('equipmentitem', 'equipmentitem.itemid = item_equipment_ids.item_id','left')
				  ->join('user_sports','user_sports.sport_id = equipment.sportid')
				 ->where('equipment_school.school_id',$school_id);
		$query = $this->db->get();
		return $query->result_array();	
	}
	/**
	* Get all equipment and groups in a joined array limited by a sport
	*
	* @access	public
	* @param	int school_id, int sport_id
	* @return	array equipment and items
	*/
	function get_equipment_and_items_sport($school_id,$sport_id)
	{
		$user_id = $this->ion_auth->user()->row()->id;
		$this->db->select('*')
				 ->from('equipment')
				 ->join('equipment_school', 'equipment_school.equipment_id = equipment.equipmentid','left')
				 ->join('item_equipment_ids', 'equipment.equipmentid = item_equipment_ids.equipment_id','left')
				 ->join('equipmentitem', 'equipmentitem.itemid = item_equipment_ids.item_id','left')
				 ->join('user_sports','user_sports.sport_id = equipment.sportid')
				 ->where(array('equipment_school.school_id'=>$school_id,'equipment.sportid'=>$sport_id));
		$query = $this->db->get();
		return $query->result_array();	
	}
	/**
	* Get an item id by its barcode and school_id
	*
	* @access	public
	* @param	int barcode, int schooL_id
	* @return	int item_id
	*/
	function get_itemid_by_barcode_and_school($barcode,$school_id)
	{
		$this->db->select('*')
				 ->from('equipmentitem')
				 ->join('item_equipment_ids', 'equipmentitem.itemid = item_equipment_ids.item_id','left')
				 ->join('equipment_school', 'item_equipment_ids.equipment_id = equipment_school.equipment_id','left')
				 ->where(array('equipment_school.school_id'=>$school_id,'equipmentitem.barcode'=>$barcode));
		$query = $this->db->get();
		foreach($query->result() as $row)
			return $row->itemid;	
	}
	/**
	* update_equipment_quantity runs through all items in an equipment group on item update, sums their quanity 
	* and updates the total group quantity
	*
	* @access	public
	* @param	int item_id
	* @return	none
	*/
	function update_equipment_quantity($item_id)
	{
		$equipmentid = $this->get_equipment_associated_item($item_id);
		$this->db->select('equipmentid, itemid, equipmentitem.quantity')
				 ->from('equipment')
				 ->join('item_equipment_ids', 'item_equipment_ids.equipment_id = equipment.equipmentid','left')
				 ->join('equipmentitem','equipmentitem.itemid = item_equipment_ids.item_id')
				 ->where('equipment.equipmentid',$equipmentid);
		$query = $this->db->get();
		$total_quantity = 0;
		foreach($query->result() as $row)
			$total_quantity = $total_quantity + $row->quantity;
		$data = array('quantity' => $total_quantity);
		$this->update_equipment($equipmentid, $data);
		
	}
	/**
	* Get what equipment_id an item is associated to
	*
	* @access	public
	* @param	int item_id
	* @return	int equipment_id
	*/
	function get_equipment_associated_item($item_id)
	{
		$this->db->select('item_id, equipment_id');
		$query = $this->db->get_where('item_equipment_ids',array('item_id' => $item_id));
		foreach($query->result() as $row)
			return $row->equipment_id;
	}
	/**
	* Get the quantity of an equipment group
	*
	* @access	public
	* @param	int equipment_id
	* @return	int quantity
	*/
	function get_equipment_quantity($equipment_id)
	{
		$this->db->select('quantity');
		$query = $this->db->get_where('equipment',array('equipmentid' => $equipment_id));
		foreach($query->result() as $row)
			return $row->quantity;
	}
	/**
	* Get the description of an equipment group
	*
	* @access	public
	* @param	int equipment_id
	* @return	string description
	*/
	function get_equipment_description($equipment_id)
	{
		$this->db->select('description');
		$query = $this->db->get_where('equipment',array('equipmentid' => $equipment_id));
		foreach($query->result() as $row)
			return $row->description;
	}
	/**
	* Associate an item to a group in the db
	*
	* @access	public
	* @param	int item_id, int equipment_id
	* @return	string 'sucess'
	*/
	function associate_item_equipment($item_id,$equipment_id)
	{
		$data = array(
			'item_id'=>$item_id,
			'equipment_id'=>$equipment_id,
		);
		$this->db->insert('item_equipment_ids',$data);
		return 'sucess';
	}
	/**
	* Update an item's group association.
	*
	* @access	public
	* @param	int item_id, int equipment_id
	* @return	none
	*/
	function update_item_association($item_id,$equipment_id)
	{
		$this->db->update('item_equipment_ids',array('item_id'=>$item_id,'equipment_id'=>$equipment_id),array('item_id' => $item_id));
	}
	/**
	* Associate a school to an equipment group.
	*
	* @access	public
	* @param	int school_id, int equipment_id
	* @return	string 'sucess'
	*/
	function associate_school_equipment($school_id,$equipment_id)
	{
		$data = array(
			'school_id'=>$school_id,
			'equipment_id'=>$equipment_id,
		);
		$this->db->insert('equipment_school',$data);
		return 'sucess';
	}
	/**
	* Get all equipment items
	*
	* @access	public
	* @param	none
	* @return	array all equipment table
	*/
	function get_all_equipment()
	{
		$this->db->select('equipmentid, equipmentname, quantity, description');
		$query = $this->db->get('equipment');
		return $query->result_array();
	}
	/**
	* Get all equipment by school
	*
	* @access	public
	* @param	int schooL_id
	* @return	array equipment
	*/
	function get_all_equipment_by_school($school_id)
	{
		$user_id = $this->ion_auth->user()->row()->id;
		$this->db->select('equipmentid, equipmentname, quantity, description, sportid ,school_id, user_sports.user_id')
				 ->from('equipment')
				 ->join('equipment_school','equipment_school.equipment_id = equipment.equipmentid')
				 ->join('user_sports','user_sports.sport_id = equipment.sportid')
				 ->where(array('school_id'=>$school_id,'user_sports.user_id'=>$user_id));
		$query = $this->db->get();
		return $query->result_array();
	}
	/**
	* Get all equipment with ids of sports
	*
	* @access	public
	* @param	int schooL_id
	* @return	array equipment
	*/
	function get_all_equipment_by_school_with_ids($school_id)
	{
		$user_id = $this->ion_auth->user()->row()->id;
		$this->db->select('equipmentid, equipmentname, quantity, description, sportid ,school_id,school_id,user_sports.user_id')
				 ->from('equipment')
				 ->join('equipment_school','equipment_school.equipment_id = equipment.equipmentid')
				 ->join('user_sports','user_sports.sport_id = equipment.sportid')
				 ->where(array('school_id'=>$school_id,'user_sports.user_id'=>$user_id));
		$query = $this->db->get();
		$results = $query->result_array();
		$equipment = array();
		foreach ($results as $line)
			$equipment[$line['equipmentid']] = $line['equipmentname'] . " - " . $this->sport_model->get_sport_name($line['sportid']) ;
        return $equipment;
	}
	/**
	* Get all equipment with ids by sport
	*
	* @access	public
	* @param	int school_id, int sport_id
	* @return	array equipment	
	*/
	function get_all_equipment_by_school_sport_with_ids($school_id,$sport_id)
	{
		$this->db->select('equipmentid, equipmentname, quantity, description, sportid ,school_id')
				 ->from('equipment')
				 ->join('equipment_school','equipment_school.equipment_id = equipment.equipmentid')
				 ->where(array('equipment_school.school_id'=>$school_id,'sportid'=>$sport_id));
		$query = $this->db->get();
		$results = $query->result_array();
		$equipment = array();
		foreach ($results as $line)
			$equipment[$line['equipmentid']] = $line['equipmentname'];
        return $equipment;
	}
	/**
	* Get all equipment items by school and sport
	*
	* @access	public
	* @param	int school_id, int sport_id
	* @return	array equipment	
	*/
	function get_all_equipment_by_school_sport($school_id,$sport_id)
	{
		$this->db->select('equipmentid, equipmentname, quantity, description, sportid ,equipment_school.school_id')
				 ->from('equipment')
				 ->join('equipment_school','equipment_school.equipment_id = equipment.equipmentid','left')
				 ->where(array('equipment_school.school_id'=>$school_id,'sportid'=>$sport_id));
		$query = $this->db->get();
		return $query->result_array();
	}
	/**
	* Get all equipment with ids
	*
	* @access	public
	* @param	none
	* @return	array equipment	
	*/
	function get_all_equipment_with_ids()
	{
		$this->db->select('equipmentid, equipmentname');
		$this->db->order_by("equipmentname", "asc");
        $query = $this->db->get('equipment');
		$results = $query->result_array();
		$equipment = array();
		foreach ($results as $line)
			$equipment[$line['equipmentid']] = $line['equipmentname'];
        return $equipment;
	}
	/**
	* Get all items associated to an equipment group
	*
	* @access	public
	* @param	int equipment_id
	* @return	array equipment items
	*/
	function get_item_associated_to_equipment($equipment_id)
	{
		$this->db->select('item_id, equipment_id');
		$query = $this->db->get_where('item_equipment_ids',array('equipment_id' => $equipment_id));
		return $query->result_array();
	}
	/**
	* Get all equipment associated to an item
	*
	* @access	public
	* @param	int item_id
	* @return	array equipment items
	*/
	function get_equipment_associated_to_item($item_id)
	{
		$this->db->select('item_id, equipment_id');
		$query = $this->db->get_where('item_equipment_ids',array('item_id' => $item_id));
		$results = $query->row();
		return $results->equipment_id;
	}
	/**
	* Get an item
	*
	* @access	public
	* @param	int item_id
	* @return	array item
	*/
	function get_item($item_id)
	{
		$this->db->select('itemid,quantity, size,  itemname, comment, barcode, assigned, cost,po_num');
		$query = $this->db->get_where('equipmentitem',array('itemid' => $item_id));
		return $query->result_array();
	}
	/**
	* Get the size of an item
	*
	* @access	public
	* @param	int item_id
	* @return	string size
	*/
	function get_item_size($item_id)
	{
		$this->db->select('size');
		$query = $this->db->get_where('equipmentitem',array('itemid' => $item_id));
		$results = $query->row();
		return $results->size;
	}
	/**
	* Get a single equipment group
	*
	* @access	public
	* @param	int equipment_id
	* @return	array equipment
	*/
	function get_equipment($equipment_id)
	{
		$this->db->select('equipmentid, equipmentname, quantity, description ,size_type, sportid');
		$query = $this->db->get_where('equipment',array('equipmentid' => $equipment_id));
		return $query->result_array();
	}
	/**
	* Get sport id of a group
	*
	* @access	public
	* @param	int equipment_id
	* @return	int sport_id
	*/
	function get_equipment_sport_id($equipment_id)
	{
		$this->db->select('sportid');
		$query = $this->db->get_where('equipment',array('equipmentid' => $equipment_id));
		$result = $query->row_array();
		return $result['sportid'];
	}
	/**
	* Decrease the number of items assigned for an equipment type
	*
	* @access	public
	* @param	int item_id, int amount=1
	* @return	int 1 or 0 (1=ERROR, 0=GOOD)
	*/
	function decrease_item_assigned($item_id,$amount)
	{	
		if($this->check_items_available($item_id,$amount))
		{
			$this->db->select('assigned');
			$query = $this->db->get_where('equipmentitem',array('itemid' => $item_id));
			$item_assigned = $query->result();
			$num = 0;
			foreach($item_assigned as $item)
				$num =  $item->assigned - $amount;
			$this->update_item($item_id,array('assigned'=>$num));
			return 1;
		}
		else
			return 0;	
	}
	/**
	* Increase the number of items assigned for an equipment type
	*
	* @access	public
	* @param	int item_id, int amount=1
	* @return	int 1 or 0 (1=ERROR, 0=GOOD)
	*/
	function increase_item_assigned($item_id,$amount=1)
	{	

		$this->db->select('assigned,quantity');
		$query = $this->db->get_where('equipmentitem',array('itemid' => $item_id));
		$item_assigned = $query->result();
		$num = 0;
		foreach($item_assigned as $item)
		{
			if($item->assigned == $item->quantity)
				return 1;
			$num =  $item->assigned + $amount;
		}	
		$this->update_item($item_id,array('assigned'=>$num));	
		return 0;	
	}
	/**
	* Check how many items are available for an item
	*
	* @access	public
	* @param	int item_id, int amount=1
	* @return	int 1 or 0 (Yes or No)
	*/
	function check_items_available($item_id,$amount=1)
	{
		$this->db->select('assigned');
		$query = $this->db->get_where('equipmentitem',array('itemid' => $item_id));
		$item_assigned = $query->result();
		$num = 0;
		foreach($item_assigned as $item)
		{
			if($item->assigned - $amount >=0)
				return 1;
			else
				return 0;
		}	
	}
	/**
	* Get the diff in quantities between items and updates
	*
	* @access	public
	* @param	int item_id, int quantity
	* @return	int num
	*/
	function get_quantity_diff($item_id,$quantity)
	{
		$this->db->select('assigned,quantity');
		$query = $this->db->get_where('equipmentitem',array('itemid' => $item_id));
		$item_assigned = $query->result();
		$num = 0;
		foreach($item_assigned as $item)
		{
			if(is_numeric($item->quantity))
			{
				$num =  $item->quantity - $quantity;
				return $num;
			}	
		}	
	}
	/**
	* Update the quantity of assignment equipment after an edit
	*
	* @access	public
	* @param	int item_id, int amount
	* @return	none
	*/
	function update_quantity_from_edit($item_id,$amount)
	{
		$this->db->select('assigned,quantity');
		$query = $this->db->get_where('equipmentitem',array('itemid' => $item_id));
		$item_assigned = $query->result();
		$num = 0;
		foreach($item_assigned as $item)
		{
			$num =  $item->assigned + $amount;
		}	
		$this->update_item('equipmentitem',$id,array('assigned'=>$num));	
	}
	/**
	* Update an equipment items by id with array of values
	*
	* @access	public
	* @param	int item_id, array equipment_values
	* @return	none
	*/
	function update_item($item_id,$array)
	{

		$this->db->update('equipmentitem',$array,array('itemid' => $item_id));
	}
	/**
	* Update an equipment group by id with array of values
	*
	* @access	public
	* @param	int equipment_id, array equipment_values
	* @return	none	
	*/
	function update_equipment($equipment_id,$array)
	{
		$this->db->update('equipment',$array,array('equipmentid' => $equipment_id));
	}
	/**
	* Delete an equipment item by id
	*
	* @access	public
	* @param	int item_id
	* @return	none
	*/
	function remove_item($item_id)
	{
		$this->db->delete('equipmentitem',array('itemid' => $item_id));
	}
	/**
	* Delete an equipment group by id
	*
	* @access	public
	* @param	int equipment_id
	* @return	none
	*/
	function remove_equipment($id)
	{
		$this->db->delete('equipment',array('equipmentid' => $equipment_id));
	}
	/**
	* Function to return equipment size types
	*
	* @access	public
	* @param	none
	* @return	associative array of size types
	*/
	function size_selection_list()
	{
		return array(
			0=>'Height',
			1=>'Weight',
			2=>'Head' ,
			3=>'Neck',
			4=>'Waist',
			5=>'Inseam',
			6=>'Cap',
			7=>'Glove' ,
			8=>'Belt' ,
			9=>'Jersey',
			10=>'Shirt',
			11=>'Pants',
			12=>'Shorts' ,
			13=>'Jock' ,
			14=>'Shoe' 
		);
	}
	/**
	* Get the total cost of an equipment group.
	*
	* @access	public
	* @param	int equipment_id
	* @return	float total_cost
	*/
	function get_equipment_total_cost($equipment_id)
	{
		$items = $this->get_item_associated_to_equipment($equipment_id);
		$total_cost = 0;
		foreach ($items as $item_row) {
			$item = $this->equipment_model->get_item($item_row['item_id']);
			foreach ($item as $item_individual)
					$total_cost = ((float)$item_individual['cost'] * (int)$item_individual['quantity']) + $total_cost;
		} 
		return (float)$total_cost;
	}
	/**
	* Get the total cost of an equipment group thats currently assigned to athletes
	*
	* @access	public
	* @param	int equipment_id
	* @return	float total_cost
	*/
	function get_equipment_total_cost_assigned($equipment_id)
	{
		$items = $this->get_item_associated_to_equipment($equipment_id);
		$total_cost = 0;
		foreach ($items as $item_row) {
			$item = $this->equipment_model->get_item($item_row['item_id']);
			foreach ($item as $item_individual)
			{
				$cost = str_replace("$", "", $item_individual['cost']);
				$total_cost = ((float)$cost * ((int)$item_individual['quantity'] - (int)$item_individual['assigned'])) + $total_cost;
			}
					
		} 
		return (float)$total_cost;
	}
	/**
	* Get total cost of all equipment in a sport
	*
	* @access	public
	* @param	int sport_id
	* @return	float total_cost
	*/
	function get_equipment_total_sport_cost($sport_id)
	{
		$equipment = $this->get_equipment_and_items_sport($this->ion_auth->user()->row()->company,$sport_id);
		$total_cost = 0;
		foreach($equipment as $equipment_item)
			$total_cost = $total_cost + $this->get_equipment_total_cost($equipment_item['equipmentid']);
		return $total_cost;
	}
	/**
	* Get the total cost of a sport of assignment equipment to athletes
	*
	* @access	public
	* @param	int sport_id
	* @return	float total_cost
	*/
	function get_equipment_total_sport_cost_assigned($sport_id)
	{
		$equipment = $this->get_equipment_and_items_sport($this->ion_auth->user()->row()->company,$sport_id);
		$total_cost = 0;
		foreach($equipment as $equipment_item)
			$total_cost = $total_cost + $this->get_equipment_total_cost_assigned($equipment_item['equipmentid']);
		return $total_cost;
	}

}
//EOF
