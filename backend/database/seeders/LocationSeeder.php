<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

/** Bangladesh delivery zones (states) and areas (cities) with delivery charges. Safe to run again. */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $bd = Country::query()->firstOrCreate(['code' => 'BD'], ['name' => 'Bangladesh', 'status' => true]);

        $zones = [
            'ঢাকার ভিতরে' => [60, [
                'Adabor', 'Badda', 'Banani', 'Bangshal', 'Cantonment', 'Chawkbazar', 'Dakshinkhan', 'Darus Salam', 'Demra', 'Dhanmondi',
                'Gendaria', 'Gulshan', 'Hazaribagh', 'Jatrabari', 'Kafrul', 'Kalabagan', 'Kamrangirchar', 'Khilgaon', 'Khilkhet', 'Kotwali',
                'Lalbagh', 'Mirpur', 'Mohammadpur', 'Motijheel', 'New Market', 'Pallabi', 'Paltan', 'Ramna', 'Rampura', 'Sabujbagh',
                'Shah Ali', 'Shahbagh', 'Sher-e-Bangla Nagar', 'Shyampur', 'Sutrapur', 'Tejgaon', 'Turag', 'Uttara', 'Uttarkhan', 'Vatara', 'Wari',
            ]],
            'ঢাকার উপশহর' => [100, ['Savar', 'Ashulia', 'Keraniganj', 'Narayanganj', 'Gazipur', 'Tongi', 'Dohar', 'Nawabganj', 'Dhamrai', 'Rupganj']],
            'ঢাকার বাহিরে' => [120, [
                'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur', 'Chapainawabganj', 'Chattogram',
                'Chuadanga', "Cox's Bazar", 'Cumilla', 'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gopalganj', 'Habiganj', 'Jamalpur',
                'Jashore', 'Jhalokati', 'Jhenaidah', 'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur',
                'Lalmonirhat', 'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh', 'Naogaon', 'Narail',
                'Narsingdi', 'Natore', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari',
                'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail', 'Thakurgaon',
            ]],
        ];

        foreach ($zones as $zone => [$cost, $areas]) {
            $state = $bd->states()->firstOrCreate(['name' => $zone], ['status' => true]);
            foreach ($areas as $area) {
                $state->cities()->firstOrCreate(['name' => $area], ['cost' => $cost, 'status' => true]);
            }
        }
    }
}
