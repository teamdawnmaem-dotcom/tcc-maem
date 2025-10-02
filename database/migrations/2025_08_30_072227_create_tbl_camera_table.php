    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('tbl_camera', function (Blueprint $table) {
            $table->id('camera_id');
                $table->string('camera_name', 50);
                $table->string('camera_ip_address', 50);
                $table->string('camera_username', 50);
                $table->string('camera_password', 50);
                $table->string('camera_live_feed', 255);
                $table->foreignId('room_no')
                    ->constrained('tbl_room', 'room_no')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('tbl_camera');
        }
    };
