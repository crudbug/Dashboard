<?php

class Integration extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'integrations';

	protected $fillable = ['name', 'user_id', 'service_provider', 'authorization_field_1', 'authorization_field_2'];

	public function nodes() {

		return $this->hasMany('node');

	}
	
	public function user() {

		return $this->belongsTo('User');

	}

}