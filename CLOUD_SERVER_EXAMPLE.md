# Cloud Server Example Implementation

## Simple Cloud Server Setup

Here's an example of how to set up a receiving endpoint on your cloud server.

## Option 1: Laravel Cloud Server

If your cloud server is also Laravel:

### Create API Controller

```php
<?php
// app/Http/Controllers/Api/SyncReceiver

Controller.php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncReceiverController extends Controller
{
    /**
     * Receive and store rooms
     */
    public function receiveRooms(Request $request)
    {
        try {
            $validated = $request->validate([
                'room_id' => 'required|integer',
                'room_no' => 'required|string',
                'room_name' => 'required|string',
                'room_building_no' => 'nullable|string',
                'room_floor_no' => 'nullable|string',
            ]);
            
            // Insert or update
            DB::table('tbl_room')->updateOrInsert(
                ['room_id' => $validated['room_id']],
                $validated
            );
            
            return response()->json(['success' => true, 'message' => 'Room synced']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Receive and store faculties
     */
    public function receiveFaculties(Request $request)
    {
        try {
            $validated = $request->validate([
                'faculty_id' => 'required|integer',
                'faculty_fname' => 'required|string',
                'faculty_lname' => 'required|string',
                'faculty_mname' => 'nullable|string',
                'faculty_department' => 'nullable|string',
                'faculty_face_embedding' => 'nullable|string',
                'faculty_images' => 'nullable|string',
                'cloud_image_urls' => 'nullable|string',
            ]);
            
            DB::table('tbl_faculty')->updateOrInsert(
                ['faculty_id' => $validated['faculty_id']],
                $validated
            );
            
            return response()->json(['success' => true, 'message' => 'Faculty synced']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Receive file upload
     */
    public function receiveFileUpload(Request $request, $directory)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:102400', // 100MB max
            ]);
            
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Store file
            $path = $file->storeAs("sync/{$directory}", $filename, 'public');
            
            // Generate URL
            $url = asset("storage/{$path}");
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get existing rooms
     */
    public function getRooms()
    {
        try {
            $rooms = DB::table('tbl_room')->select('room_id')->get();
            return response()->json($rooms);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }
    
    /**
     * Get sync status
     */
    public function getSyncStatus()
    {
        try {
            $lastSync = DB::table('sync_logs')->orderBy('created_at', 'desc')->first();
            
            return response()->json([
                'status' => 'ok',
                'message' => 'Cloud server is ready',
                'last_sync' => $lastSync ? $lastSync->created_at : null,
                'counts' => [
                    'rooms' => DB::table('tbl_room')->count(),
                    'faculties' => DB::table('tbl_faculty')->count(),
                    'attendance_records' => DB::table('tbl_attendance_record')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Add Routes (routes/api.php)

```php
Route::middleware('auth:api')->group(function () {
    // GET endpoints (return existing data)
    Route::get('/rooms', [SyncReceiverController::class, 'getRooms']);
    Route::get('/cameras', [SyncReceiverController::class, 'getCameras']);
    Route::get('/faculties', [SyncReceiverController::class, 'getFaculties']);
    // ... other GET endpoints
    
    // POST endpoints (receive new data)
    Route::post('/rooms', [SyncReceiverController::class, 'receiveRooms']);
    Route::post('/cameras', [SyncReceiverController::class, 'receiveCameras']);
    Route::post('/faculties', [SyncReceiverController::class, 'receiveFaculties']);
    Route::post('/teaching-loads', [SyncReceiverController::class, 'receiveTeachingLoads']);
    Route::post('/attendance-records', [SyncReceiverController::class, 'receiveAttendanceRecords']);
    // ... other POST endpoints
    
    // File uploads
    Route::post('/upload/{directory}', [SyncReceiverController::class, 'receiveFileUpload']);
    
    // Status
    Route::get('/sync-status', [SyncReceiverController::class, 'getSyncStatus']);
});
```

### Add API Authentication

```php
// app/Http/Middleware/ApiKeyAuth.php

namespace App\Http\Middleware;

use Closure;

class ApiKeyAuth
{
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('Authorization');
        
        if (!$apiKey || $apiKey !== 'Bearer ' . env('API_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
}
```

Register middleware in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
];
```

Update routes:

```php
Route::middleware('api.key')->group(function () {
    // ... all your sync routes
});
```

## Option 2: Node.js/Express Cloud Server

```javascript
// server.js
const express = require('express');
const mysql = require('mysql2/promise');
const multer = require('multer');
const path = require('path');

const app = express();
app.use(express.json());

// Database connection
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: 'password',
    database: 'tcc_cloud',
    waitForConnections: true,
    connectionLimit: 10
});

// API Key middleware
const apiKeyAuth = (req, res, next) => {
    const apiKey = req.headers.authorization;
    if (!apiKey || apiKey !== `Bearer ${process.env.API_KEY}`) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    next();
};

app.use(apiKeyAuth);

// Get rooms
app.get('/api/rooms', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT room_id FROM tbl_room');
        res.json(rows);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Receive rooms
app.post('/api/rooms', async (req, res) => {
    try {
        const { room_id, room_no, room_name, room_building_no, room_floor_no } = req.body;
        
        await pool.query(
            `INSERT INTO tbl_room (room_id, room_no, room_name, room_building_no, room_floor_no) 
             VALUES (?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             room_no=VALUES(room_no), 
             room_name=VALUES(room_name)`,
            [room_id, room_no, room_name, room_building_no, room_floor_no]
        );
        
        res.json({ success: true, message: 'Room synced' });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// File upload
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const dir = path.join(__dirname, 'uploads', req.params.directory);
        cb(null, dir);
    },
    filename: (req, file, cb) => {
        cb(null, Date.now() + '_' + file.originalname);
    }
});

const upload = multer({ storage });

app.post('/api/upload/:directory', upload.single('file'), (req, res) => {
    try {
        const url = `${req.protocol}://${req.get('host')}/uploads/${req.params.directory}/${req.file.filename}`;
        res.json({ success: true, url });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Sync status
app.get('/api/sync-status', async (req, res) => {
    try {
        const [rooms] = await pool.query('SELECT COUNT(*) as count FROM tbl_room');
        const [faculties] = await pool.query('SELECT COUNT(*) as count FROM tbl_faculty');
        
        res.json({
            status: 'ok',
            message: 'Cloud server is ready',
            counts: {
                rooms: rooms[0].count,
                faculties: faculties[0].count
            }
        });
    } catch (error) {
        res.status(500).json({ status: 'error', message: error.message });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Cloud server running on port ${PORT}`);
});
```

## Option 3: Python/Flask Cloud Server

```python
# app.py
from flask import Flask, request, jsonify
import mysql.connector
import os
from werkzeug.utils import secure_filename

app = Flask(__name__)

# Database connection
def get_db():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="password",
        database="tcc_cloud"
    )

# API Key middleware
def require_api_key():
    api_key = request.headers.get('Authorization')
    if not api_key or api_key != f"Bearer {os.getenv('API_KEY')}":
        return jsonify({'error': 'Unauthorized'}), 401

@app.before_request
def check_api_key():
    if request.endpoint != 'health':
        result = require_api_key()
        if result:
            return result

# Get rooms
@app.route('/api/rooms', methods=['GET'])
def get_rooms():
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute("SELECT room_id FROM tbl_room")
        rooms = cursor.fetchall()
        cursor.close()
        db.close()
        return jsonify(rooms)
    except Exception as e:
        return jsonify({'error': str(e)}), 500

# Receive rooms
@app.route('/api/rooms', methods=['POST'])
def receive_rooms():
    try:
        data = request.json
        db = get_db()
        cursor = db.cursor()
        
        query = """
        INSERT INTO tbl_room (room_id, room_no, room_name, room_building_no, room_floor_no)
        VALUES (%s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE 
        room_no=VALUES(room_no), room_name=VALUES(room_name)
        """
        
        cursor.execute(query, (
            data['room_id'],
            data['room_no'],
            data['room_name'],
            data.get('room_building_no'),
            data.get('room_floor_no')
        ))
        
        db.commit()
        cursor.close()
        db.close()
        
        return jsonify({'success': True, 'message': 'Room synced'})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# File upload
@app.route('/api/upload/<directory>', methods=['POST'])
def upload_file(directory):
    try:
        if 'file' not in request.files:
            return jsonify({'success': False, 'error': 'No file'}), 400
        
        file = request.files['file']
        filename = secure_filename(file.filename)
        filepath = os.path.join('uploads', directory, filename)
        
        os.makedirs(os.path.dirname(filepath), exist_ok=True)
        file.save(filepath)
        
        url = f"{request.host_url}uploads/{directory}/{filename}"
        return jsonify({'success': True, 'url': url})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# Sync status
@app.route('/api/sync-status', methods=['GET'])
def sync_status():
    try:
        db = get_db()
        cursor = db.cursor()
        
        cursor.execute("SELECT COUNT(*) FROM tbl_room")
        room_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM tbl_faculty")
        faculty_count = cursor.fetchone()[0]
        
        cursor.close()
        db.close()
        
        return jsonify({
            'status': 'ok',
            'message': 'Cloud server is ready',
            'counts': {
                'rooms': room_count,
                'faculties': faculty_count
            }
        })
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
```

## Database Setup (Cloud Server)

Use the same database schema as your local server:

```sql
-- Run all your migrations on the cloud database
-- Or import your schema:
mysqldump -u root -p tcc_maem > schema.sql
mysql -u root -p tcc_cloud < schema.sql
```

## Testing Your Cloud Server

### Test Authentication

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://your-cloud-server.com/api/sync-status
```

### Test Receiving Data

```bash
curl -X POST https://your-cloud-server.com/api/rooms \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{"room_id":1,"room_no":"101","room_name":"Test Room"}'
```

### Test File Upload

```bash
curl -X POST https://your-cloud-server.com/api/upload/faculty_images \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -F "file=@/path/to/image.jpg"
```

## Security Best Practices

1. **Always use HTTPS**
2. **Generate strong API keys**: `openssl rand -hex 32`
3. **Rate limiting**: Implement rate limits on cloud server
4. **Input validation**: Validate all incoming data
5. **SQL injection prevention**: Use parameterized queries
6. **File upload security**: Validate file types and sizes
7. **Logging**: Log all sync operations
8. **Backups**: Regular database backups

## Summary

Choose the option that matches your cloud server stack:
- **Option 1**: Laravel (same as local)
- **Option 2**: Node.js/Express
- **Option 3**: Python/Flask

All options provide the same functionality:
✅ Receive data via POST
✅ Return existing data via GET
✅ Handle file uploads
✅ API key authentication
✅ Status endpoint

