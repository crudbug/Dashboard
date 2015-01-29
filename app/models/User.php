<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends Eloquent implements UserInterface, RemindableInterface {

	use UserTrait, RemindableTrait;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes not excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];
	protected $fillable = ['password', 'name', 'avatar', 'email', 'username', 'full_name'];

	public function integrations() {
	
		return $this->hasMany('Integration');
	
	}

	public function nodes() {
		return $this->hasMany('Node', 'owner_id');
	}
	
	public function unmanaged_nodes() {
		return $this->hasMany('Node', 'owner_id')->where('managed', '=', 'false')->groupBy('service_provider_cluster_id');
	}
	
	public function managed_nodes() {
		return $this->hasMany('Node', 'owner_id')->where('managed', '=', 'true');
	}

}
