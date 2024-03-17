<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Validator;
use App\Models\{contacts, categories, properties, User, addresses};
use Exception;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class api extends Controller
{


    public function login(Request $req) {
        $validator = Validator::make($req->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required'
        ]);
        if(!$validator->fails()) {
            $user = User::where('email', $req->email)->first();
            if(Hash::check($req->password, $user->password)) {
                return response()->json([
                    'token' => $user->createToken('authToken')->plainTextToken,
                    'user' => $user
                ]);
            } else {
                return response()->json(['Error' =>  __('auth.failed')], 401);
            }
            return 'success';
        } else {
            return response()->json(['Error' => $validator->errors()->all()], 401);
        }
    }

    public function logout(Request $req) {
        $req->user()->tokens()->delete();
        return response()->json(['Success' => 'Logged out successfully'], 200);
    }

    public function register(Request $req) {
        $validator = Validator::make($req->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        if(!$validator->fails()) {
            $user = User::create([
                'name' => $req->name,
                'email' => $req->email,
                'password' => Hash::make($req->password),
                'email_verified_at' => null
                // 'password' => bcrypt($req->password)
            ]);

            event(new Registered($user));
            
            return response()->json([
                'token' => $user->createToken('authToken')->plainTextToken,
                'user' => $user
            ]);
        } else {
            return response()->json(['Error' => $validator->errors()->all()], 401);
        }
    }

    public function verify(Request $req) {
        $user = User::findOrFail($req->id);
        if($user->hasVerifiedEmail()) {
            return response()->json('User aleardy verified', 200);
        } else {
            if(!hash_equals((string) $req->hash, sha1($user->getEmailForVerification()))) {
                return response()->json("Invalid verification code", 401);
            } else {
                if($user->markEmailAsVerified()) {
                    event(new Verified($user));
                    return response()->json('Email verified successfully', 200);
                } else {
                    return response()->json('Email not verified', 401);
                }

            }
        }
    }

    public function sendVerificationEmail(Request $req) {
        if($req->user()->hasVerifiedEmail()) {
            return response()->json('User already verified', 200);
        } else {
            // if($req->user()->sendEmailVerificationNotification()) {
                $req->user()->sendEmailVerificationNotification();
                return response()->json('Email verification link sent on your email', 200);
            // } else {
            //     return response()->json('Somthing wrong!');
            // }

        }
    }

    public function forgot(Request $req) {
        $validator = Validator::make($req->all(), [
            'email' => 'required|email|exists:Users,email'
        ]); 
        if(!$validator->fails()) {
            $status = Password::sendResetLink($req->only('email'));
            if($status == Password::RESET_LINK_SENT) {
                return response()->json(['Success' => __($status)], 200);
            } else {
                return response()->json(['Error' => __('password.throttled')], 401);
            }
        } else {
            return response()->json(['Error' => $validator->errors()->all()], 401);
        }
    }

    public function reset(Request $req) {
        $validator = Validator::make($req->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:Users,email',
            'password' => 'required|min:6'
        ]);
        if(!$validator->fails()) {
            $status = Password::reset(
                $req->only('email', 'password', 'token'),
                function($user) use ($req) {
                    $user->forceFill([
                        'password' => Hash::make($req->password),
                        'remember_token' => Str::random(60)
                    ])->save();
                    $user->tokens()->delete();
                    event(new PasswordReset($user));
                }
            );
            if($status == Password::PASSWORD_RESET) {
                return response()->json(['Success' => __($status)], 200);
            } else {
                return response()->json(['Error' => [__($status)]], 401);
            }
        } else {
            return response()->json(['Error' => $validator->errors()->all()], 401);
        }
    }


    public function profile(Request $req) {
        // return $req->user();
        return Auth::user();
    }

    public function profileProperties(Request $req) {
        // return properties::where('user_id', $req->user()->id)->get();
        return properties::OfUser(Auth::id())->withTrashed()->paginate(10);
    }

    public function deleteProperty($id) {
        $property = properties::where(['id' => $id, 'user_id' => Auth::id()])->first();
        if($property) {
            $property->delete();
            return response()->json(['Success' => 'Property deleted successfully'], 200);
        } else {
            return response()->json(['Error' => 'Property not found'], 401);
        }
    }

    public function trashedProperty(Request $req) {
        return properties::OfUser(Auth::id())->onlyTrashed()->get();
    }

    public function addProperty(Request $req) {
        $validator = Validator::make($req->all(),[
            // 'category_id' => 'required|exists:categories,id',
            // 'address_id' => 'required|exists:addresses,id',
            'category_name' => 'required|exists:categories,name',
            'title' => 'required|min:3',
            'description' => 'required|min:3',
            'price' => 'required|numeric',
            'area' => 'required|numeric',
            'bedroom' => 'nullable|numeric',
            'bathroom' => 'nullable|numeric',
            'garage' => 'nullable|numeric',
            'kitchen' => 'nullable|numeric',
            'image' => 'required|image|max:2048',
            'country' => 'required|min:3',
            'city' => 'required|string',
            'location' => 'required|json'
        ]);

        if(!$validator->fails()) {
            DB::beginTransaction();
            try {
                $category_id = categories::where('name', $req->category_name)->get('id')->pluck('id')[0];
                $address_id = addresses::insertGetId([
                    'country' => $req->country,
                    'city' => $req->city,
                    'location' => $req->location,
                ]);
                // upload image -----------------------------------------------------------------------
                $image_name = uniqid() . '.' . $req->image->extension();
                // $path = $req->image->storeAs('public/images', $image_name);
                // $path = $req->file('image')->store('public/images');
                // $path = $req->image->move(public_path('images'), $image_name);
                // ------------------------------------------------------------------------------------
                $property = properties::create([
                    'user_id' => Auth::id(),
                    'category_id' => $category_id,
                    'address_id' => $address_id,
                    'title' => $req->title,
                    'description' => $req->description,
                    'price' => $req->price,
                    'area' => $req->area,
                    'bedroom' => $req->bedroom,
                    'bathroom' => $req->bathroom,
                    'garage' => $req->garage,
                    'kitchen' => $req->kitchen,
                    'image' => $image_name,
                ]);
                $path = $req->image->storeAs('public/images', $image_name);
                if($property) {
                    DB::commit();
                    return response()->json(['Success' => 'The element has been inserted'], 200);
                } else {
                    DB::commit();
                    return response()->json(['Error' => __($property)], 401);
                }
            } catch(\Exception $e) {
                DB::rollback();
                return response()->json(['Error' => 'Something wrong!'], 401);
            }
        } else {
            return response()->json(['Error' => $validator->errors()->all()], 401);
        }
    }



    public function home(Request $req) {
        // Debugbar::disable(); 
        info($req->all());
        return [
            'categories' => categories::latest()->get(),
            // 'newest' => properties::latest()->take(5)->get()->makeHidden('images')
            'newest' => properties::OfUser($req->user_id)
                ->OfCategory($req->category_id)
                ->OfCity($req->city_name)
                ->ofSearch($req->search)
                ->ofPrice($req->price)
                ->ofArea($req->area)
                ->latest()
                ->take(5)
                ->get(),
            'users' => User::latest()->take(5)->get(),
        ];
    }

    public function contact(Request $req) {
        // info($req->all());

        $validator = Validator::make($req->all(), [
            'full_name' => 'required|min:3',
            'email' => 'required|email',
            'phone_number' => 'required|max:11',
            'message' => 'required|min:3'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()->all()], 401);
        } else {
            // return contacts::create([
            //     'full_name' => $req->full_name,
            //     'email' => $req->email,
            //     'phone_number' => $req->phone_number,
            //     'message' => $req->message
            // ]); 

            return response()->json(['success' => 'Message sent successfully'], 200);
        }
    }
    
    public function properties(Request $req) {
        return [
        'newest' => properties::OfUser($req->user_id)
            ->OfCategory($req->category_id)
            ->OfCity($req->city_name)
            ->ofSearch($req->search)
            ->ofPrice($req->price)
            ->ofArea($req->area)
            ->paginate(10)
        ];
    }

    public function property(Request $req) {
        // $property = properties::find($req->id);
        // if($property) {
        //     return $property;
        // } else {
        //     return response()->json(['Error' => 'Property Not Found'], 404);
        // }

        return properties::with(['category', 'address', 'user'])->findOrFail($req->id);
    }

    public function users(Request $req) {
        return User::ofSearch($req->search)->latest()->paginate(10);
    }

    public function user(Request $req) {
        return User::findOrFail($req->id);
    }

    public function categories(Request $req) {
        return categories::latest()->get();
    }

    public function all() {
        $realestate = properties::all();
        $useres = User::all('name', 'email');
        $all = [
            'real_estate' => $realestate,
            'useres' => $useres
        ];
        return $all;
    }

}
