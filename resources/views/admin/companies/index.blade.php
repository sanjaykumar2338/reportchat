@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4">
    <h2 style="margin-left: 176px;">Companies Management</h2>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.companies.index') }}" style="width: 92%; margin-left: 176px;" class="mb-3">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Search by Company Name" value="{{ request('name') }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Reset</a>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('admin.companies.create') }}" class="btn btn-success">Add Company</a>
            </div>
        </div>
    </form>

    <!-- Notification Button -->
    <div class="text-end mb-3" style="width: 92%; margin-left: 176px;">
        <button type="button" id="openNotificationModal" class="btn btn-primary" disabled>Send Notification</button>
    </div>

    <table class="table table-bordered" style="width: 92%; margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th><input type="checkbox" id="selectAllCompanies"></th>
                <th>ID</th>
                <th>Company Name</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @if($companies->count() > 0)
                @foreach($companies as $company)
                <tr>
                    <td><input type="checkbox" class="company-checkbox" value="{{ $company->id }}"></td>
                    <td>{{ $company->id }}</td>
                    <td>{{ $company->name }}</td>
                    <td>{{ $company->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('admin.companies.edit', $company->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.companies.destroy', $company->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <strong>No companies found.</strong>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Pagination -->
    {{ $companies->links('vendor.pagination.bootstrap-5') }}
</div>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content p-4 shadow-sm">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">Send Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="sendNotificationForm">
          @csrf
          <input type="hidden" name="companies" id="selectedCompanies">
          <div class="mb-3">
            <label>Notification Title</label>
            <input type="text" class="form-control" name="title" required />
          </div>
          <div class="mb-3">
            <label>Message</label>
            <textarea class="form-control" name="message" rows="4" required></textarea>
          </div>
          <button type="submit" class="btn btn-success">Send</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openModalBtn = document.getElementById('openNotificationModal');
    const checkboxes = document.querySelectorAll('.company-checkbox');
    const selectedCompaniesField = document.getElementById('selectedCompanies');

    checkboxes.forEach(chk => {
        chk.addEventListener('change', () => {
            const selected = Array.from(checkboxes).filter(c => c.checked);
            openModalBtn.disabled = selected.length === 0;
        });
    });

    openModalBtn.addEventListener('click', () => {
        const selected = Array.from(checkboxes)
            .filter(c => c.checked)
            .map(c => c.value);
        selectedCompaniesField.value = selected.join(',');
        const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
        modal.show();
    });

    document.getElementById('sendNotificationForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('{{ url("/admin/companies/send-notification") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message || 'Notification sent!');
            location.reload();
        })
        .catch(err => {
            alert('Error sending notification');
        });
    });

    document.getElementById('selectAllCompanies').addEventListener('change', function () {
        checkboxes.forEach(c => c.checked = this.checked);
        openModalBtn.disabled = !this.checked;
    });
});
</script>
@endsection
