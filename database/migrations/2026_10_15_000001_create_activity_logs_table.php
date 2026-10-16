<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel Activity Log / Audit Trail.
     *
     * Catatan retensi: data log disimpan SELAMANYA. Tidak ada mekanisme
     * auto-delete / pembersihan berbasis waktu. Kolom user_id dibuat
     * nullable() + nullOnDelete() agar penghapusan akun user tidak ikut
     * menghapus jejak audit; nama pelaku tetap disimpan pada kolom
     * user_name (snapshot) dan di dalam kolom description.
     */
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel users (nullable agar log tetap abadi bila user dihapus)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable(); // snapshot nama pelaku

            // Jenis aksi: CREATE, UPDATE, DELETE, LOGIN, LOGOUT, dsb.
            $table->string('action', 50);

            // Modul tempat aksi dilakukan (mis. "Invoicing & PO")
            $table->string('module_name');

            // Subjek/model yang terpengaruh (morph) beserta deskripsi naratif
            $table->nullableMorphs('subject');
            $table->text('description');

            // Perubahan data lama vs baru dalam bentuk JSON (opsional)
            $table->json('properties')->nullable();

            $table->timestamps();

            $table->index(['module_name', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
