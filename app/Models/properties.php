<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class properties extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'image' => 'array'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
    

    protected $appends = ['image', 'images'];
    public function getImageAttribute() {
        $first_image = json_decode($this->attributes['image'])[0];
        return asset('upload/properties/' . $first_image);
    }
    public function getImagesAttribute() {
        $images = [];
        $all_images = json_decode($this->attributes['image']);
        foreach($all_images as $image) {
            $images[] = asset('upload/properties/' . $image);
        }
        return $images;
    }


    public function scopeOfUser($query, $user_id) {
        if($user_id) {
            return $query->where('user_id', $user_id);
        } else {
            return $query;
        }
    }

    public function scopeOfCategory($query, $category_id) {
        if($category_id) {
            return $query->where('category_id', $category_id);
        } else {
            return $query;
        }
    }

    public function scopeOfCity($query, $city_name) {
        if($city_name) {
            $city_id = [];
            $addresses = Addresses::where('city', $city_name)->get('id');
            $addresses = $addresses[0]->attributes;
            foreach($addresses as $address) {
                $city_id[] = $address;
            }
            // return $query->where('address_id', $city_id[0]);
            return $query->where('address_id', $city_id[0]);
        } else {
            return $query;
        }
    }

    public function scopeOfSearch($query, $search) {
        if($search) {
            return $query->where('title', 'LIKE', '%' . $search . '%')->orWhere('description', 'LIKE', '%' . $search . '%');
        } else {
            return $query;
        }
    }

    public function scopeOfPrice($query, $price) {
        if($price) {
            return $query->whereBetween('price', [$price[0], $price[1]]);
        } else {
            return $query;
        }
    }

    public function scopeOfArea($query, $area) {
        if($area) {
            return $query->whereBetween('area', [$area[0], $area[1]]);
        } else {
            return $query;
        }
    }


    public function category() {
        return $this->belongsTo(categories::class);
    }

    public function address() {
        return $this->belongsTo(addresses::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

}
