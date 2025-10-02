<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id();
            $table->string('cml_id')->unique();
            $table->string('created')->nullable();
            $table->text('modified')->nullable();
            $table->json('lab_description')->nullable();
            $table->integer('node_count')->nullable();
            $table->string('state')->nullable();
            $table->string('lab_title')->nullable();
            $table->string('owner')->nullable();
            $table->integer('link_count')->nullable(); //link_count
            $table->array('effective_permissions')->nullable(); //link_count
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labs');
    }
};
