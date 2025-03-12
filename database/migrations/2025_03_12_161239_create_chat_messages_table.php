<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade'); // Links to chat session
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // User who sent the message
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null'); // Admin who sent the message
            $table->text('message')->nullable();
            $table->string('image')->nullable(); // Image attachment
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
};
