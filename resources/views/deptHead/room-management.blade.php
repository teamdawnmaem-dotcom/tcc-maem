    @extends('layouts.appdeptHead')

    @section('title', 'Room Management - Tagoloan Community College')
    @section('monitoring-active', 'active')
    @section('rooms-active', 'active')

    @section('styles')
        <style>
            .faculty-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                margin-bottom: 40px;
            }

            .faculty-title-group {
                display: flex;
                flex-direction: column;
            }

            .faculty-title {
                font-size: 2.3rem;
                font-weight: bold;
                color: #6d0000;
            }

            .faculty-subtitle {
                font-size: 1rem;
                color: #666;
                margin-bottom: 30px;
            }

            .faculty-actions-row {
                display: flex;
                gap: 10px;
                position: absolute;
                top: 130px;
                right: 40px;
                z-index: 100;
            }

            .search-input {
                padding: 8px;
                font-size: 14px;
                border: 1px solid #ccc;
                border-radius: 4px;
                width: 400px;
            }

        

            .add-btn {
                padding: 8px 24px;
                font-size: 14px;
                border: none;
                border-radius: 4px;
                background-color: #2ecc71;
                color: #fff;
                cursor: pointer;
                font-weight: bold;
            }

            .faculty-table-container {
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 1.5px 8px rgba(0, 0, 0, 0.12);
                overflow: hidden;
            }

            .faculty-table {
                width: 100%;
                border-collapse: collapse;
            }

            .faculty-table th {
                background: #8B0000;
                color: #fff;
                padding: 16px 0;
                font-size: 1.1rem;
                font-weight: bold;
                border: none;
            }

            /* Keep table header visible while scrolling */
            .faculty-table thead th {
                position: sticky;
                top: 0;
                z-index: 2;
            }

            .faculty-table td {
                padding: 12px 0;
                text-align: center;
                font-size: 1rem;
                border: none;
            }

            .faculty-table tr:nth-child(even) {
                background: #fff;
            }

            .faculty-table tr:nth-child(odd) {
                background: #fbeeee;
            }

            .faculty-table tr:hover {
                background: #fff2e6;
            }

            /* Make only the table area scroll vertically */
            .faculty-table-scroll {
                max-height: 670px;
                overflow-y: auto;
                width: 100%;
            }

            .action-btns {
                display: flex;
                gap: 8px;
                justify-content: center;
                align-items: center;
            }

            .edit-btn,
            .delete-btn {
                width: 40px;
                height: 32px;
                border-radius: 6px;
                border: 2px solid #111;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.1rem;
                font-weight: bold;
                background: #fff;
                transition: box-shadow 0.2s;
                box-shadow: none;
                outline: none;
                padding: 0;
                cursor: pointer;
            }

            .edit-btn {
                background: #7cc6fa;
                color: #fff;
                border: none;
            }

            .delete-btn {
                background: #ff3636;
                color: #fff;
                border: none;
            }

            .edit-btn:active,
            .delete-btn:active {
                box-shadow: 0 0 0 2px #2222;
            }

            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }

            

            .modal-header {
                font-size: 2rem;
                font-weight: bold;
                color: #8B0000;
                text-align: center;
                margin-bottom: 28px;
            }

            .modal-img-box {
                border: 2px dashed #222;
                border-radius: 10px;
                width: 220px;
                height: 180px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 18px;
            }

            .modal-plus {
                font-size: 3.5rem;
                color: #111;
                font-weight: bold;
            }

            .modal-form {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: stretch;
            }

            .modal-form-group {
                display: flex;
                flex-direction: column;
                gap: 8px;
                position: relative;
                padding-bottom: 18px;
            }

            .modal-form-group label {
                margin-bottom: 6px;
                font-size: 1rem;
            }

            .modal-form-group input,
            .modal-form-group select {
                width: 100%;
                padding: 10px 12px;
                font-size: 1rem;
                border: 1px solid #bbb;
                border-radius: 5px;
            }
            .modal-form-group input.valid,
            .modal-form-group select.valid { border-color:#2ecc71; box-shadow:0 0 0 2px rgba(46,204,113,0.1); }
            .modal-form-group input.invalid,
            .modal-form-group select.invalid { border-color:#ff3636; box-shadow:0 0 0 2px rgba(255,54,54,0.1); }
            .validation-message { position:absolute; left:0; right:12px; bottom:0; font-size:0.85rem; color:#ff3636; pointer-events:none; padding-left:12px; }

            .modal-btn {
                width: 100%;
                padding: 14px 0;
                font-size: 1.1rem;
                font-weight: bold;
                border: none;
                border-radius: 6px;
                margin-top: 14px;
                cursor: pointer;
            }

            .modal-btn.add {
                background: #2ecc71;
                color: #fff;
            }

            .modal-btn.update {
                background: #7cc6fa;
                color: #fff;
            }

            .modal-btn.cancel {
                background: #fff;
                color: #800000;
                border: 2px solid #800000;
                border-radius: 8px;
                padding: 10px 20px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .modal-btn.cancel:hover {
                background: #800000;
                color: #fff;
            }

            /* Delete button style to match user account */
            .modal-btn.delete {
                background: transparent;
                color: #ff3636;
                border: 2px solid #ff3636;
            }
            .modal-btn.delete:hover {
                background: #ff3636;
                color: #fff;
            }


            .modal-row {
                display: flex;
                gap: 18px;
                width: 100%;
            }

            .modal-form-group.half {
                flex: 1;
            }

            .modal-form-group select {
                width: 100%;
                padding: 6px 10px;
                font-size: 1rem;
                border: 1px solid #bbb;
                border-radius: 5px;
            }

            .modal-form-group input[readonly] {
                background: #eee;
                color: #888;
            }

            .modal-buttons {
                display: flex;
                gap: 12px;
                justify-content: center;
            }

            .modal-box {
                align-items: center;
                width: 400px;
                max-width: 95vw;
                background: #fff;
                padding: 0;
                overflow: hidden;
            }

            .modal-form {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: stretch;
            }

            .modal-form-group {
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 12px;
                margin-bottom: 12px;
            }
            #deleteRoomModal .modal-header { margin-top: 25px; margin-bottom: 0; }


            .modal-form-group label {
                min-width: 130px;
                text-align: left;
                font-size: 1rem;
                color: #222;
            }

            .modal-form-group input {
                flex: 1;
                width: 100%;
                padding: 10px 12px;
                font-size: 1rem;
                border: 1px solid #bbb;
                border-radius: 5px;
            }

            .modal-buttons {
                display: flex;
                gap: 12px;
                justify-content: center;
                margin-top: 10px;
            }

            .modal-btn {
                width: 50%;
                padding: 14px 0;
                font-size: 1.1rem;
                font-weight: bold;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            }

            .modal-btn.add {
                color: #fff;
                background: #2ecc71;
            }

            .modal-btn.add:hover {
                background: #27ae60;
            }

            .modal-btn.update {
                color: #fff;
                background: #3498db;
            }

            .modal-btn.update:hover {
                background: #5bb3f5;
                color: #fff;
            }
        </style>
    @endsection

    @section('content')
    @include('partials.flash')
    @include('partials.flashupdate')
    @include('partials.flashdelete')

        <div class="faculty-header">
            <div class="faculty-title-group">
                <div class="faculty-title">Room Management</div>
                <div class="faculty-subtitle"></div>
            </div>
            <div class="faculty-actions-row">
                <input type="text" class="search-input" id="roomSearch" placeholder="Search...">
                <button class="add-btn" onclick="openModal('addRoomModal')">Add</button>
            </div>
        </div>

        <div class="faculty-table-container">
            <div class="faculty-table-scroll">
            <table class="faculty-table">
                <thead>
                    <tr>
                        <th>Room No.</th>
                        <th>Room Name</th>
                        <th>Building No.</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="roomTable">
                    @forelse($rooms as $room)
                    <tr>
                        <td>{{ $room->room_no }}</td>
                        <td>{{ $room->room_name }}</td>
                        <td>{{ $room->room_building_no }}</td>
                        <td>
                            <div class="action-btns">
                                <button class="edit-btn" 
                                    onclick="openUpdateModal({{ $room->room_no }}, '{{ $room->room_name }}', '{{ $room->room_building_no }}')">&#9998;</button>
                                <button class="delete-btn" 
                                    onclick="openDeleteModal({{ $room->room_no }})">&#128465;</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; font-style:italic; color:#666;">
                            No Registered Camera found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <!-- Add Room Modal -->
        <div id="addRoomModal" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
                <form method="POST" action="{{ route('deptHead.room.store') }}" style="padding: 0;">
                    @csrf
                    <div class="modal-header" style="
                        background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        ADD ROOM
                    </div>
                    <div class="modal-form" style="padding: 24px 24px 24px;">
                        <style>
                            #addRoomModal .modal-form-group { display:flex; align-items:center; gap:6px; margin-bottom:4px; padding-bottom:6px; position:relative; }
                            #addRoomModal .modal-form-group label { min-width:130px; margin-bottom:0; font-size:1rem; text-align:left; }
                            #addRoomModal .modal-form-group input, #addRoomModal .modal-form-group select { flex:1; width:100%; padding:10px 12px; font-size:1rem; border:1px solid #bbb; border-radius:5px; }
                            #addRoomModal .validation-message { font-size:0.8rem; left:130px; right:10px; bottom:-10px; padding-left:10px; line-height:1.1; position:absolute; color:#ff3636; pointer-events:none; }
                            #addRoomModal .modal-buttons { display:flex; gap:12px; justify-content:center; margin-top:12px; }
                            #addRoomModal .modal-btn.add { background: transparent; border: 2px solid #2e7d32; color: #2e7d32; }
                            #addRoomModal .modal-btn.add:hover { background: #2e7d32; color: #fff; border-color: #2e7d32; }
                        </style>
                        <div class="modal-form-group">
                            <label>Room Name :</label>
                            <input type="text" name="room_name" placeholder="Enter room name">
                        </div>
                        <div class="modal-form-group">
                            <label>Building No. :</label>
                            <input type="text" name="room_building_no" placeholder="Enter building number">
                        </div>
                        <div class="modal-buttons">
                            <button type="submit" class="modal-btn add">Add</button>
                            <button type="button" class="modal-btn cancel" onclick="closeModal('addRoomModal')">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Room Modal -->
        <div id="updateRoomModal" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="padding: 0; overflow: hidden; border-radius: 8px;">
                <form method="POST" id="updateRoomForm" style="padding: 0;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="room_no" id="updateRoomNo">
                    <div class="modal-header" style="
                        background-color: #8B0000; color: white; padding: 18px 24px; font-size: 24px; font-weight: bold; width: 100%; margin: 0; display: flex; align-items: center; justify-content: center; text-align: center; letter-spacing: 0.5px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        UPDATE ROOM
                    </div>
                    <div class="modal-form" style="padding: 24px 24px 24px;">
                        <style>
                            #updateRoomModal .modal-form-group { display:flex; align-items:center; gap:6px; margin-bottom:4px; padding-bottom:6px; position:relative; }
                            #updateRoomModal .modal-form-group label { min-width:130px; margin-bottom:0; font-size:1rem; text-align:left; }
                            #updateRoomModal .modal-form-group input, #updateRoomModal .modal-form-group select { flex:1; width:100%; padding:10px 12px; font-size:1rem; border:1px solid #bbb; border-radius:5px; }
                            #updateRoomModal .validation-message { font-size:0.8rem; left:130px; right:10px; bottom:-10px; padding-left:10px; line-height:1.1; position:absolute; color:#ff3636; pointer-events:none; }
                            #updateRoomModal .modal-buttons { display:flex; gap:12px; justify-content:center; margin-top:12px; }
                            #updateRoomModal .modal-btn.update { background: #7cc6fa; color: #fff; border: 2px solid #7cc6fa; }
                            #updateRoomModal .modal-btn.update:hover { background: #5bb3f5; color: #fff; border-color: #5bb3f5; }
                        </style>
                        <div class="modal-form-group">
                            <label>Room Name :</label>
                            <input type="text" name="room_name" id="updateRoomName">
                        </div>
                        <div class="modal-form-group">
                            <label>Building No. :</label>
                            <input type="text" name="room_building_no" id="updateBuildingNo">
                        </div>
                        <div class="modal-buttons">
                            <button type="submit" class="modal-btn update">Update</button>
                            <button type="button" class="modal-btn cancel" onclick="closeModal('updateRoomModal')">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Room Modal -->
        <div id="deleteRoomModal" class="modal-overlay" style="display:none;">
            <form id="deleteRoomForm" method="POST" class="modal-box">
                @csrf
                @method('DELETE')
                <div class="modal-header delete">DELETE ROOM</div>
                <div style="padding: 20px 24px 24px;">
                <div style="text-align: center; margin: 30px;">
                    <div style="font-size: 4rem; color: #ff3636; margin-bottom: 20px;">⚠️</div>
                    <div style="font-size: 1.2rem; color: #333; margin-bottom: 10px; font-weight: bold;">Are you sure?</div>
                    <div style="font-size: 1rem; color: #666; line-height: 1.5;">This action cannot be undone. The room and its associated camera and live feed will be permanently deleted.</div>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="modal-btn delete">Delete Room</button>
                    <button type="button" class="modal-btn cancel" onclick="closeModal('deleteRoomModal')">Cancel</button>
                </div>
            </form>
        </div>
    @endsection

    @section('scripts')
    <script>
        // Store existing room names for validation
        const existingRoomNames = @json($rooms->pluck('room_name')->toArray());
        
        function openModal(modalId){
            document.getElementById(modalId).style.display = 'flex';
        }
        function resetModalForm(modalId){
            const modal = document.getElementById(modalId);
            if(!modal) return;
            const form = modal.querySelector('form');
            if(!form) return;

            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(function(el){
                if(el.tagName === 'SELECT'){
                    el.value = '';
                } else if (el.type === 'checkbox' || el.type === 'radio'){
                    el.checked = false;
                } else {
                    el.value = '';
                }
                el.classList.remove('valid','invalid');
                el.dataset.touched = 'false';
            });

            form.querySelectorAll('.validation-message').forEach(function(msg){
                msg.textContent = '';
            });

            window.roomSubmitAttempt = false;
        }
        function closeModal(modalId){
            const modal = document.getElementById(modalId);
            if(modal) modal.style.display = 'none';
            if(modalId === 'addRoomModal' || modalId === 'updateRoomModal'){
                resetModalForm(modalId);
            }
        }

        function openUpdateModal(room_no, room_name, room_building_no){
            openModal('updateRoomModal');
            document.getElementById('updateRoomNo').value = room_no;
            document.getElementById('updateRoomName').value = room_name;
            document.getElementById('updateBuildingNo').value = room_building_no;
            document.getElementById('updateRoomForm').action = '/deptHead/rooms/' + room_no;
            
            // Store original room name for uniqueness validation
            const form = document.getElementById('updateRoomForm');
            form.dataset.origRoomName = room_name || '';
        }

        function openDeleteModal(room_no){
            openModal('deleteRoomModal');
            document.getElementById('deleteRoomForm').action = '/deptHead/rooms/' + room_no;
        }

        // =========================
// Responsive Table Search with "No results found"
// =========================
document.querySelector('.search-input').addEventListener('input', function() {
    let searchTerm = this.value.toLowerCase();
    let rows = document.querySelectorAll('.faculty-table tbody tr');
    let anyVisible = false;

    rows.forEach(row => {
        // Skip the "no results" row if it exists
        if(row.classList.contains('no-results')) return;

        let text = row.textContent.toLowerCase();
        if(text.includes(searchTerm)){
            row.style.display = '';
            anyVisible = true;
        } else {
            row.style.display = 'none';
        }
    });

    // Handle "no results" row
    let tbody = document.querySelector('.faculty-table tbody');
    let noResultsRow = tbody.querySelector('.no-results');

    if(!anyVisible){
        if(!noResultsRow){
            noResultsRow = document.createElement('tr');
            noResultsRow.classList.add('no-results');
            noResultsRow.innerHTML = `<td colspan="4" style="text-align:center; padding:20px; color:#999; font-style:italic;">No results found</td>`;
            tbody.appendChild(noResultsRow);
        }
    } else {
        if(noResultsRow) noResultsRow.remove();
    }
});

// =========================
// Client-side Validation (Room forms)
// =========================
(function(){
    function trim(v){ return (v||'').trim(); }
    function isNotEmpty(v){ return trim(v).length>0; }
    function isRoomNameTaken(value, currentRoomName = '') {
        const trimmedValue = trim(value);
        if (!trimmedValue) return false; // Don't check if empty
        if (trimmedValue === currentRoomName) return false; // Don't check against current room's own name
        return existingRoomNames.includes(trimmedValue);
    }
    function setValidity(el, ok){ if(!el) return; const show = el.dataset.touched==='true' || window.roomSubmitAttempt===true; el.classList.remove('valid','invalid'); if(!show) return; el.classList.add(ok?'valid':'invalid'); }
    function setMessage(el, msg){ if(!el) return; const g=el.closest('.modal-form-group'); if(!g) return; let m=g.querySelector('.validation-message'); if(!m){ m=document.createElement('div'); m.className='validation-message'; g.appendChild(m);} const show = el.dataset.touched==='true' || window.roomSubmitAttempt===true; m.textContent= show ? (msg||'') : ''; }

    function validateAdd(){
        const name = document.querySelector("#addRoomModal [name='room_name']");
        const bno = document.querySelector("#addRoomModal [name='room_building_no']");
        const vName = isNotEmpty(name&&name.value) && !isRoomNameTaken(name&&name.value);
        const vBno = isNotEmpty(bno&&bno.value);
        setValidity(name,vName); setMessage(name,vName?'':(isNotEmpty(name&&name.value)?'Room name is already taken':'Room name is required'));
        setValidity(bno,vBno); setMessage(bno,vBno?'':'Building number is required');
        return vName && vBno;
    }

    function validateUpdate(){
        const name = document.getElementById('updateRoomName');
        const bno = document.getElementById('updateBuildingNo');
        const form = document.getElementById('updateRoomForm');
        const origRoomName = form ? form.dataset.origRoomName : '';
        const vName = isNotEmpty(name&&name.value) && !isRoomNameTaken(name&&name.value, origRoomName);
        const vBno = isNotEmpty(bno&&bno.value);
        setValidity(name,vName); setMessage(name,vName?'':(isNotEmpty(name&&name.value)?'Room name is already taken':'Room name is required'));
        setValidity(bno,vBno); setMessage(bno,vBno?'':'Building number is required');
        return vName && vBno;
    }

    ['#addRoomModal [name="room_name"]','#addRoomModal [name="room_building_no"]'].forEach(sel=>{
        const el=document.querySelector(sel); if(!el) return; const evt=el.tagName==='SELECT'?'change':'input'; el.addEventListener(evt, validateAdd); el.addEventListener('blur', ()=>{ el.dataset.touched='true'; validateAdd(); });
    });
    ['#updateRoomName','#updateBuildingNo'].forEach(sel=>{
        const el=document.querySelector(sel); if(!el) return; el.addEventListener('input', validateUpdate);
    });

    (function(){
        const addForm = document.querySelector('#addRoomModal form');
        if(addForm){ addForm.addEventListener('submit', function(e){ window.roomSubmitAttempt=true; if(!validateAdd()){ e.preventDefault(); }}); }
        const updForm = document.getElementById('updateRoomForm');
        if(updForm){ updForm.addEventListener('submit', function(e){ window.roomSubmitAttempt=true; if(!validateUpdate()){ e.preventDefault(); }}); }
    })();
})();

    // Close + reset when clicking outside (overlay)
    document.addEventListener('click', function(e){
        if(e.target.classList && e.target.classList.contains('modal-overlay')){
            const overlayId = e.target.id;
            e.target.style.display = 'none';
            if(overlayId === 'addRoomModal' || overlayId === 'updateRoomModal'){
                resetModalForm(overlayId);
            }
        }
    });
    </script>
    @endsection