@php
    $updateMsg = session('update_success') ?? session('updated') ?? session('success_update') ?? null;
@endphp
@if ($updateMsg)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Updated Successfully!',
                text: '{{ $updateMsg }}',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#8B0000',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: true,
                allowOutsideClick: true,
                allowEscapeKey: true,
                customClass: {
                    popup: 'swal2-popup-custom',
                    title: 'swal2-title-custom',
                    content: 'swal2-content-custom'
                }
            });
        });
    </script>
@endif
