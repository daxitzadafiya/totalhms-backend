<?php
namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use JWTAuth;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements WithHeadingRow, WithValidation, ToCollection
{
//    /**
//     * @param array $row
//     *
//     * @return \Illuminate\Database\Eloquent\Model|null
//     */
//    public function model(array $row)
//    {
//        $user = JWTAuth::parseToken()->authenticate();
//
//        return new User([
//            'company_id' => $user['company_id'],
//            'added_by' => $user['id'],
//            'first_name' => $row['first_name'],
//            'last_name' => $row['last_name'],
//            'email' => $row['email'],
//            'phone_number' => $row['phone_number'],
//            'personal_number' => $row['personal_number'],
//            'password' => '123123',
//        ]);
//
//    }
    public function collection(Collection $rows)
    {
        $user = JWTAuth::parseToken()->authenticate();

        foreach ($rows as $row)
        {
            $user = User::create([
                'company_id' => $user['company_id'],
                'added_by' => $user['id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone_number' => $row['phone_number'],
                'personal_number' => $row['personal_number']
            ]);

            Employee::create([
                'user_id' => $user['id'],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|unique:users,email',
            'phone_number' => 'required',
            'personal_number' => 'required',
        ];
    }
}
