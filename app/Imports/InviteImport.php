<?php

namespace App\Imports;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmailContent;
use App\Models\EmailLog;
use App\Models\Invite;
use App\Models\JobTitle;
use App\Models\User;
use App\Notifications\InviteMember;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class InviteImport extends Controller implements WithHeadingRow, ToCollection
{
    protected $company_id;

    public function  __construct($company_id)
    {
        $this->company_id= $company_id;
    }

    public function collection(Collection $rows)
    {
        try{
            foreach ($rows as $row)
            {
                $department = Department::where('name', @$row['department_name'])->first();
                $jobTitle = JobTitle::where('name', @$row['job_title_name'])->first();

                if (empty($this->company_id) || empty($row['email'])) {
                    continue;
                }
                $existInvite = Invite::where('company_id', $this->company_id)->where('email', $row['email'])->first();
                if ($existInvite) {
                    continue;
                }
                $inviteUser = Invite::updateOrCreate(
                    [
                        'email' => $row['email'],
                    ],
                    [
                        'first_name' => @$row['first_name'],
                        'last_name' => @$row['last_name'],
                        'company_id' => @$this->company_id,
                        'email' => @$row['email'],
                        'phone_number' => @$row['phone_number'],
                        'personal_number' => @$row['personal_number'],
                        'address' => @$row['address'],
                        'city' => @$row['city'],
                        'zip_code' => @$row['zip_code'],
                        'department_id' => @$department->id,
                        'job_title_id' => @$jobTitle->id,
                    ]);

                $token = sha1(time() . $inviteUser->id);
                $today = new \DateTime('now');

                $inviteUser->token = $token;
                $inviteUser->expired_time = $today->add(new \DateInterval('P2D'));
                $inviteUser->save();

                $emailContent = EmailContent::where('key', 'invite_member')->first();
                $emailDescription = str_replace('{user_name}', $inviteUser->first_name . ' ' . $inviteUser->last_name, $emailContent['description']);

                $url = URL::signedRoute('invitation.show', ['token' => $token]);

                if ($inviteUser->email) {
                    try {
                        Notification::route('mail', $inviteUser->email)
                            ->notify(new InviteMember($emailContent, $emailDescription, $url));
                        $emailStatus = EmailLog::SENT;
                    } catch (\Exception $e) {
                        info('notify-invite member, Erro:' . $e->getMessage());
                        $emailStatus = EmailLog::FAIL;
                    }

                    EmailLog::create([
                        'company_id' => $inviteUser->company_id,
                        'type' => $emailContent->title,
                        'description' => $emailDescription,
                        'status' => $emailStatus,
                        'for_admin' => 1,
                        'for_company' => 1,
                    ]);
                    $admin = User::where('role_id',1)->first();
                    $this->pushNotification($admin->id, null, 2, [$admin->id], 'invite', 'send_invite', $inviteUser->id, $inviteUser->first_name, 'send');
                }
            }
        }catch(\Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
