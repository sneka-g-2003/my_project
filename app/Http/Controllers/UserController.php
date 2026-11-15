<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    //
	
public function saveContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'zipcode' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');

        $contact = DB::table('user')
            ->where('email', $email)
            ->first();

        $data = [
            'name'       => $request->input('name'),
            'email'      => $email,
            'phone'      => $request->input('phone'),
            'address'    => $request->input('address'),
            'zipcode'    => $request->input('zipcode'),
            'updated_at' => now(),
        ];

        if ($contact) {
            // Existing email: update the record
            DB::table('user')
                ->where('email', $email)
                ->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Contact updated successfully'
            ]);
        }

        // New email: insert a record
        $data['created_at'] = now();

        DB::table('user')->insert($data);

        return response()->json([
            'status' => true,
            'message' => 'Contact inserted successfully'
        ], 201);
    }

public function getUser(Request $request)
{
    $email = $request->input('email');

    $user = DB::table('user')
        ->where('email', $email)
        ->first();

//dd($user);


    return response()->json([
        'status' => true,
        'data' => $user
    ]);
}



public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->input('email'))->first();

    if (!$user || !Hash::check($request->input('password'), $user->password)) {
        return response()->json([
            'status'  => false,
            'message' => 'Invalid email or password',
        ], 401);
    }

    $token = $user->createToken('login')->plainTextToken;

    return response()->json([
        'status'       => true,
        'message'      => 'Login successful',
        'access_token' => $token,
        'token_type'   => 'Bearer',
    ]);
}
 public function signup(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        DB::table('user')->insert([
            'email'      => $request->input('email'),
            'password'   => Hash::make($request->input('password')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'User inserted successfully',
        ], 201);
    }



 public function insertPet(Request $request)
    {
        // 1. Get the logged-in user
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated user',
            ], 401);
        }

        $uid = $user->id;

        // 2. Validate the request
        $validator = Validator::make($request->all(), [
            'pet_image'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'name'       => 'required|string|max:255',
            'type'       => 'required|string|max:100',
            'breed'      => 'nullable|string|max:100',
            'dob'        => 'nullable|date',
            'weight'     => 'nullable|numeric|min:0',
            'color'      => 'nullable|string|max:100',
            'microchipid'=> 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 3. Upload the pet image
        $imagePath = null;

        if ($request->hasFile('pet_image')) {
            $imagePath = $request->file('pet_image')
                ->store('pets', 'public');
        }

        // 4. Insert pet details into the database
        $petId = DB::table('pets')->insertGetId([
            'user_id'     => $uid,
            'pet_image'   => $imagePath,
            'name'        => $request->input('name'),
            'type'        => $request->input('type'),
            'breed'       => $request->input('breed'),
            'dob'         => $request->input('dob'),
            'weight'      => $request->input('weight'),
            'color'       => $request->input('color'),
            'microchipid' => $request->input('microchipid'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 5. Return the response
        return response()->json([
            'status'  => true,
            'message' => 'Pet details inserted successfully',
            'data'    => [
                'pet_id'      => $petId,
                'user_id'     => $uid,
                'pet_image'   => $imagePath,
                'image_url'   => $imagePath
                    ? asset('storage/' . $imagePath)
                    : null,
                'name'        => $request->input('name'),
                'type'        => $request->input('type'),
                'breed'       => $request->input('breed'),
                'dob'         => $request->input('dob'),
                'weight'      => $request->input('weight'),
                'color'       => $request->input('color'),
                'microchipid' => $request->input('microchipid'),
            ],
        ], 201);
    }


public function selectPets(Request $request)
{
    // Get logged-in user from Bearer Token
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated user',
        ], 401);
    }

    // Logged-in user's ID
    $uid = $user->id;

    // Get only this user's pets
    $pets = DB::table('pets')
        ->where('user_id', $uid)
        ->orderBy('id', 'desc')
        ->get();

    // Add image URL to each pet
    $pets = $pets->map(function ($pet) {
        $pet->image_url = $pet->pet_image
            ? asset('storage/' . $pet->pet_image)
            : null;

        return $pet;
    });

    return response()->json([
        'status'  => true,
        'message' => $pets->isEmpty()
            ? 'No pets found for this user'
            : 'Pets fetched successfully',
        'data'    => $pets,
    ], 200);
}



public function insertRoutine(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'pet_id'       => 'required|integer',
        'routine_type' => 'required|in:Feed,Walk,Meds,Sleep',
        'title'        => 'required|string|max:255',
        'description'  => 'nullable|string',
        'routine_date' => 'required|date_format:Y-m-d',
        'routine_time' => 'required|date_format:H:i',
    ]);

    // Check whether the pet belongs to the logged-in user
    $pet = DB::table('pets')
        ->where('id', $request->input('pet_id'))
        ->where('user_id', $user->id)
        ->first();

    if (!$pet) {
        return response()->json([
            'success' => false,
            'message' => 'Pet not found for this user'
        ], 404);
    }

    $id = DB::table('pet_routines')->insertGetId([
        'user_id'      => $user->id,
        'pet_id'       => $pet->id,
        'routine_type' => $request->input('routine_type'),
        'title'        => $request->input('title'),
        'description'  => $request->input('description'),
        'routine_date' => $request->input('routine_date'),
        'routine_time' => $request->input('routine_time'),
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $routine = DB::table('pet_routines')
        ->where('id', $id)
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'Routine added successfully',
        'data'    => $routine
    ], 201);
}


public function selectRoutine(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'pet_id'       => 'nullable|integer',
        'routine_date' => 'nullable|date_format:Y-m-d',
        'routine_type' => 'nullable|in:Feed,Walk,Meds,Sleep',
    ]);

    $query = DB::table('pet_routines')
        ->where('user_id', $user->id);

    if ($request->filled('pet_id')) {
        $query->where('pet_id', $request->input('pet_id'));
    }

    if ($request->filled('routine_date')) {
        $query->where(
            'routine_date',
            $request->input('routine_date')
        );
    }

    if ($request->filled('routine_type')) {
        $query->where(
            'routine_type',
            $request->input('routine_type')
        );
    }

    $routines = $query
        ->orderBy('routine_date', 'asc')
        ->orderBy('routine_time', 'asc')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Routines retrieved successfully',
        'data'    => $routines
    ], 200);
}

public function updateRoutine(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'id'           => 'required|integer',
        'pet_id'       => 'required|integer',
        'routine_type' => 'required|in:Feed,Walk,Meds,Sleep',
        'title'        => 'required|string|max:255',
        'description'  => 'nullable|string',
        'routine_date' => 'required|date_format:Y-m-d',
        'routine_time' => 'required|date_format:H:i',
    ]);

    $routine = DB::table('pet_routines')
        ->where('id', $request->input('id'))
        ->where('user_id', $user->id)
        ->first();

    if (!$routine) {
        return response()->json([
            'success' => false,
            'message' => 'Routine not found'
        ], 404);
    }

    $pet = DB::table('pets')
        ->where('id', $request->input('pet_id'))
        ->where('user_id', $user->id)
        ->first();

    if (!$pet) {
        return response()->json([
            'success' => false,
            'message' => 'Pet not found for this user'
        ], 404);
    }

    DB::table('pet_routines')
        ->where('id', $request->input('id'))
        ->where('user_id', $user->id)
        ->update([
            'pet_id'       => $pet->id,
            'routine_type' => $request->input('routine_type'),
            'title'        => $request->input('title'),
            'description'  => $request->input('description'),
            'routine_date' => $request->input('routine_date'),
            'routine_time' => $request->input('routine_time'),
            'updated_at'   => now(),
        ]);

    $updatedRoutine = DB::table('pet_routines')
        ->where('id', $request->input('id'))
        ->where('user_id', $user->id)
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'Routine updated successfully',
        'data'    => $updatedRoutine
    ], 200);
}



public function deleteRoutine(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'id' => 'required|integer',
    ]);

    $routine = DB::table('pet_routines')
        ->where('id', $request->input('id'))
        ->where('user_id', $user->id)
        ->first();

    if (!$routine) {
        return response()->json([
            'success' => false,
            'message' => 'Routine not found'
        ], 404);
    }

    DB::table('pet_routines')
        ->where('id', $request->input('id'))
        ->where('user_id', $user->id)
        ->delete();

    return response()->json([
        'success' => true,
        'message' => 'Routine deleted successfully'
    ], 200);
}



public function todayRoutine(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'pet_id' => 'required|integer',
    ]);

    // Check whether the pet belongs to the logged-in user
    $pet = DB::table('pets')
        ->where('id', $request->input('pet_id'))
        ->where('user_id', $user->id)
        ->first();

    if (!$pet) {
        return response()->json([
            'success' => false,
            'message' => 'Pet not found for this user'
        ], 404);
    }

    // Get today's routines for this user and pet
    $routines = DB::table('pet_routines')
        ->where('user_id', $user->id)
        ->where('pet_id', $pet->id)
        ->whereDate('routine_date', today()->toDateString())
        ->orderBy('routine_time', 'asc')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Today routines retrieved successfully',
        'date'    => today()->toDateString(),
        'pet'     => [
            'id'   => $pet->id,
            'name' => $pet->name,
            'type' => $pet->type
        ],
        'data' => $routines
    ], 200);
}


public function upcomingRoutine(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $request->validate([
        'pet_id' => 'required|integer',
    ]);

    // Check whether the pet belongs to the logged-in user
    $pet = DB::table('pets')
        ->where('id', $request->input('pet_id'))
        ->where('user_id', $user->id)
        ->first();

    if (!$pet) {
        return response()->json([
            'success' => false,
            'message' => 'Pet not found for this user'
        ], 404);
    }

    // Get upcoming routines starting tomorrow
    $routines = DB::table('pet_routines')
        ->where('user_id', $user->id)
        ->where('pet_id', $pet->id)
        ->whereDate('routine_date', '>', today()->toDateString())
        ->orderBy('routine_date', 'asc')
        ->orderBy('routine_time', 'asc')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Upcoming routines retrieved successfully',
        'pet' => [
            'id'   => $pet->id,
            'name' => $pet->name,
            'type' => $pet->type
        ],
        'data' => $routines
    ], 200);
}

}
