<?php
 
 namespace Database\Seeders;
 
 use App\Models\User;
 use Illuminate\Database\Seeder;
 use Spatie\Permission\Models\Role;
 
 class UserSeeder extends Seeder
 {
     /**
      * Run the database seeds.
      */
     public function run(): void
     {
         // Super Admins (Developers/Owners)
         $superAdmins = [
             [
                 'name' => 'Jhonny Pirela (77)',
                 'email' => 'jhosagid77@gmail.com',
                 'password' => bcrypt('jhosagid'),
                 'profile' => 'Super Admin',
             ],
             [
                 'name' => 'Jhonny Sagid Pirela',
                 'email' => 'jhosagid7@gmail.com',
                 'password' => bcrypt('jhosagid'),
                 'profile' => 'Super Admin',
             ],
         ];
 
         foreach ($superAdmins as $data) {
             $user = User::updateOrCreate(
                 ['email' => $data['email']],
                 [
                     'name' => $data['name'],
                     'password' => $data['password'],
                     'profile' => $data['profile'],
                     'status' => 'Active',
                     'commission_percentage' => 0,
                 ]
             );
             $user->assignRole('Super Admin');
         }
 
         // Generic Seller for Testing
         $seller = User::updateOrCreate(
             ['email' => 'vendedor@prueba.com'],
             [
                 'name' => 'Vendedor de Prueba',
                 'password' => bcrypt('12345678'),
                 'profile' => 'Vendedor',
                 'status' => 'Active',
                 'commission_percentage' => 5.00,
             ]
         );
         $seller->assignRole('Vendedor');

         // Operarios Reales de Fábrica (JSBolsas Pro)
         $operators = [
             ['name' => 'Gabriel Marquez', 'email' => 'gabriel@plasticosmyf.com'],
             ['name' => 'Ernesto',         'email' => 'ernesto@plasticosmyf.com'],
             ['name' => 'Sahir',           'email' => 'sahir@plasticosmyf.com'],
             ['name' => 'Victor',          'email' => 'victor@plasticosmyf.com'],
             ['name' => 'Nestor',          'email' => 'nestor@plasticosmyf.com'],
         ];

         foreach ($operators as $op) {
             $operatorUser = User::updateOrCreate(
                 ['email' => $op['email']],
                 [
                     'name' => $op['name'],
                     'password' => bcrypt('12345678'),
                     'profile' => 'operario',
                     'status' => 'Active',
                     'weekly_salary' => 90.00,
                     'work_days_per_week' => 6,
                 ]
             );
             if (Role::where('name', 'operario')->orWhere('name', 'Operario')->exists()) {
                 $roleName = Role::where('name', 'operario')->exists() ? 'operario' : 'Operario';
                 $operatorUser->assignRole($roleName);
             }
         }
     }
 }
