<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ContactFile
 * 
 * @property int $contact_id
 * @property int $file_id
 * 
 * @property Contact $contact
 * @property File $file
 *
 * @package App\Models
 */
class ContactFile extends Model
{
	protected $table = 'contact_file';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'contact_id' => 'int',
		'file_id' => 'int'
	];

	public function contact()
	{
		return $this->belongsTo(Contact::class);
	}

	public function file()
	{
		return $this->belongsTo(File::class);
	}
}
