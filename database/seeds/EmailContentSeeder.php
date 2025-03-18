<?php

use App\Models\EmailContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('email_contents')->delete();
        $tmp1 = EmailContent::create(array(
            'id' => 1,
            'key' => 'coupon_code',
            'title' => 'Coupon Code',
            'subject' => 'Discount couple code',
            'source_code' => '{company_name},{coupon_code}',
            'description' => 'Hello {company_name}, We hope this email finds you well and enjoying your experience with our subscription service. As a valued subscriber, we are excited to present you with a limited-time offer! We are providing an exclusive discount coupon that you can apply to your next subscription. Here your coupon code: {coupon_code}. Thank You',
        ));

        $tmp2 = EmailContent::create(array(
            'id' => 2,
            'key' => 'reminder_invoice',
            'title' => 'Reminder invoice',
            'subject' => 'Reminder invoice',
            'is_sms' => 1,
            'source_code' => '{company_name}',
            'description' => 'Hello {company_name}, This is reminder for invoice',
            'sms_description' => 'Hello {company_name}, This is reminder for invoice',
        ));

        $tmp3 = EmailContent::create(array(
            'id' => 3,
            'key' => 'plan_purchased',
            'title' => 'Plan purchased',
            'subject' => 'Plan purchased',
            'source_code' => '{company_name},{plan_name}',
            'description' => 'Hello {company_name}, Your new plan {plan_name} has been purchased, thank you.',
        ));

        $tmp4 = EmailContent::create(array(
            'id' => 4,
            'key' => 'plan_changed',
            'title' => 'Plan changed',
            'subject' => 'Plan changed',
            'source_code' => '{company_name},{new_plan},{old_plan}',
            'description' => 'Hello {company_name}, Thank you for changing the new plan {new_plan} from {old_plan}',
        ));

        $tmp5 = EmailContent::create(array(
            'id' => 5,
            'key' => 'plan_cancelled',
            'title' => 'Plan cancelled',
            'subject' => 'Plan cancelled',
            'source_code' => '{company_name},{plan_name}',
            'description' => 'Hello {company_name}, The {plan_name} Plan has been canceled.'
        ));

        $tmp6 = EmailContent::create(array(
            'id' => 6,
            'key' => 'plan_renewed',
            'title' => 'Plan Renewed',
            'subject' => 'Plan Renewed',
            'source_code' => '{company_name},{plan_name}',
            'description' => 'Hello {company_name}, I have renewed your plan {plan_name}, thank you.'
        ));

        $tmp7 = EmailContent::create(array(
            'id' => 7,
            'key' => 'addon_purchased',
            'title' => 'Addon purchased',
            'subject' => 'Addon purchased',
            'source_code' => '{company_name},{addon_name}',
            'description' => 'Hello {company_name}, Your new addon {addon_name} has been purchased, thank you.'
        ));

        $tmp8 = EmailContent::create(array(
            'id' => 8,
            'key' => 'addon_cancelled',
            'title' => 'Addon cancelled',
            'subject' => 'Addon cancelled',
            'source_code' => '{company_name},{addon_name}',
            'description' => 'Hello {company_name}, The {addon_name} Addon has been canceled.'
        ));

        $tmp9 = EmailContent::create(array(
            'id' => 9,
            'key' => 'addon_renewed',
            'title' => 'Addon Renewed',
            'subject' => 'Addon Renewed',
            'source_code' => '{company_name},{addon_name}',
            'description' => 'Hello {company_name}, I have renewed your addon {addon_name}, thank you.'
        ));
        $tmp10 = EmailContent::create(array(
            'id' => 10,
            'key' => 'immediately_cancel_subscription',
            'title' => 'Immediately Cancel Subscription',
            'subject' => 'Immediately Cancel Subscription',
            'source_code' => '{company_name}',
            'description' => 'Hello {company_name}, You have been notified that I have cancelled your subscription immediately, thank you.'
        ));
        $tmp11 = EmailContent::create(array(
            'id' => 11,
            'key' => 'invite_customer_service',
            'title' => 'Invite Customer Service',
            'subject' => 'Invite Customer Service',
            'source_code' => '{user_name}',
            'description' => 'Hello {user_name}, Join the invitation for super admin, thank you.'
        ));

        $tmp12 = EmailContent::create(array(
            'id' => 12,
            'key' => 'invite_member',
            'title' => 'Invite Member',
            'subject' => 'Invite Member',
            'source_code' => '{user_name}',
            'description' => 'Hello {user_name}, Join the invitation for super admin, thank you.'
        )); 

        $tmp13 = EmailContent::create(array(
            'id' => 13,
            'key' => 'accept_invite',
            'title' => 'Accept Invite',
            'subject' => 'Accept Invite',
            'source_code' => '{user_name}',
            'description' => 'Hello {user_name}, Accepted invitaion, thank you.'
        )); 
        $tmp14 = EmailContent::create(array(
            'id' => 14,
            'key' => 'reminder_free_trails_end',
            'title' => 'Reminder Free Trails End',
            'subject' => 'Reminder Free Trails End',
            'is_sms' => 0,
            'source_code' => '{company_name}',
            'description' => 'Hello {company_name}, There are 5 days left for free trails to end',
        ));
        $tmp15 = EmailContent::create(array(
            'id' => 15,
            'key' => 'reminder_plan_cancel',
            'title' => 'Reminder Plan Cancel',
            'subject' => 'Reminder Plan Cancel',
            'is_sms' => 0,
            'source_code' => '{company_name},{plan_name}',
            'description' => 'Hello {company_name}, {plan_name} Plan has been deleted by supper admin,please change your plan before expiry date.',
        ));
        $tmp16 = EmailContent::create(array(
            'id' => 16,
            'key' => 'reminder_addon_cancel',
            'title' => 'Reminder Addon Cancel',
            'subject' => 'Reminder Addon Cancel',
            'is_sms' => 0,
            'source_code' => '{company_name},{addon_name}',
            'description' => 'Hello {company_name}, {addon_name} Addon has been deleted by supper admin,please change your addon before expiry date.',
        ));
    }
}