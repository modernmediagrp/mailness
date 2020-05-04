<?php

use Illuminate\Database\Seeder;
use App\Contact;
use Illuminate\Support\Str;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        
        foreach (range(1, 10000) as $i) {
            $contact = new Contact();
            
            $contact->email = "test1300{$i}@example.com";
            $contact->list_id = 1;
            $contact->save();

            // $contact->refresh();
        }
    }

}
