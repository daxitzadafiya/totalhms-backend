<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('original_file_name')->nullable();
            $table->float('file_size')->default(0)->nullable();
            $table->string('uri')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('industry_id')->nullable();
            $table->integer('status')->default(0)->nullable()->comment('0: draft, 1: public');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('deviation_id')->nullable();
            $table->unsignedBigInteger('report_id')->nullable();
            $table->integer('report_question_id')->nullable()->comment('a value of question_id on table reports');
            $table->unsignedBigInteger('absence_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('risk_element_source_id')->nullable();
            $table->text('help_center_id')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->integer('delete_status')->default(0)->nullable();
            $table->string('type')->nullable() -> comment("for enhancement");
            $table->integer('added_from')->default(1)->comment('1: document, 2: company, 3: contact, 4: employee, 5: deviation, 6: checklist, 7: risk element source, 8: absence, 9: project, 10: help center');
            $table->boolean('is_template')->default(false);
            $table->bigInteger('parent_id')->nullable();
            $table->integer('type_of_attachment')->nullable()->comment('1: Attachment, 2: Only note');
            $table->boolean('is_renewed')->nullable();
            $table->integer('renewed_option')->comment('1: Only me, 2: Admin, 3: Custom')->nullable();
            $table->string('renewed_department_array')->nullable();
            $table->string('renewed_job_title_array')->nullable();
            $table->string('renewed_project_array')->nullable();
            $table->string('renewed_employee_array')->nullable();
            $table->date('deadline')->nullable();
            $table->boolean('is_public')->nullable();
            $table->boolean('show_manager')->nullable();
            $table->unsignedBigInteger('added_by_department_id')->nullable();
            $table->string('security_department_array')->nullable();
            $table->string('security_project_array')->nullable();
            $table->string('security_employee_array')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('deviation_id')->references('id')->on('deviations')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users');
            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');
            $table->foreign('absence_id')->references('id')->on('absences')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('risk_element_source_id')->references('id')->on('risk_element_sources')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
}
