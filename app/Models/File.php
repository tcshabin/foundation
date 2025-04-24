<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class File
 * 
 * @property int $id
 * @property string $path
 * @property string $name
 * @property int $size
 * @property string $type
 * @property int $is_temp
 * @property Carbon $created_at
 * 
 * @property Collection|Contact[] $contacts
 *
 * @package App\Models
 */
class File extends Model
{
	protected $table = 'file';
	public $timestamps = false;

	protected $casts = [
		'size' => 'int',
		'is_temp' => 'int'
	];

	protected $fillable = [
		'path',
		'name',
		'size',
		'type',
		'is_temp'
	];

	public function contacts()
	{
		return $this->belongsToMany(Contact::class);
	}
}
