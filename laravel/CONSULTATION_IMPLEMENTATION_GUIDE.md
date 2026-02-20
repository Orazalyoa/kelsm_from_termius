# Legal Consultation Module Implementation Guide

## Overview

This document provides a complete guide for the legal consultation module implementation in the Kelisim application.

## Architecture

### Workflow

1. **User creates consultation** → Status: `pending`
2. **Admin assigns lawyer** → Status: `assigned` + Chat room created automatically
3. **Lawyer accepts** → Status: `in_progress`
4. **Work completed** → Status: `completed` or `cancelled`

### Key Features

- Multi-file upload with version tracking
- Automatic chat room creation on lawyer assignment
- Status change audit logging
- Role-based access control (lawyers cannot create consultations)
- Real-time updates via chat integration

## Database Setup

### Run Migrations

```bash
cd kelisim-backend
php artisan migrate
```

### Seed Admin Menu

```bash
php artisan db:seed --class=ConsultationMenuSeeder
```

## Configuration

### Environment Variables

Add to `.env`:

```env
CONSULTATION_MAX_FILE_SIZE=536870912  # 512MB
CONSULTATION_ALLOWED_FILE_TYPES=doc,docx,pdf,jpg,jpeg,png,zip
```

### File Storage

Ensure storage is properly linked:

```bash
php artisan storage:link
```

## API Endpoints

### User API Routes (Protected by JWT)

```
GET    /api/consultations              - List consultations
POST   /api/consultations              - Create consultation
GET    /api/consultations/statistics   - Get statistics
GET    /api/consultations/{id}         - Get consultation details
PUT    /api/consultations/{id}         - Update consultation (pending only)
PUT    /api/consultations/{id}/status  - Update status
POST   /api/consultations/{id}/files   - Upload file
GET    /api/consultations/{id}/files/{fileId}          - Download file
GET    /api/consultations/{id}/files/{fileId}/versions - Get file versions
DELETE /api/consultations/{id}/files/{fileId}          - Delete file
```

### Admin Routes

```
GET    /admin/consultations                      - List all consultations
GET    /admin/consultations/{id}                 - View consultation details
POST   /admin/consultations/{id}/assign-lawyer   - Assign lawyer (creates chat)
PUT    /admin/consultations/{id}                 - Update consultation
```

## Frontend Integration

### Pages

1. **Consultation List** (`/pages/consultation/index`)
   - View all user consultations
   - Filter by status
   - View statistics

2. **Consultation Detail** (`/pages/consultation/detail`)
   - View consultation details
   - See files with versions
   - Access chat room (if assigned)
   - View status history

3. **Create Consultation** (`/pages/consultation/create`)
   - Select topic
   - Enter title and description
   - Set priority
   - Attach files

### Integration with Home Page

The "Submit New Application" button on the home page now navigates to `/pages/consultation/create`.

## File Versioning

### How It Works

1. User uploads file "contract.pdf" → Version 1 created
2. User uploads "contract.pdf" again → Version 2 created (linked to V1)
3. API returns all versions when requested
4. Download specific version by file ID

### File Structure

```
consultation_files
├── id: 1, file_name: contract.pdf, version: 1, parent_file_id: NULL
└── id: 2, file_name: contract.pdf, version: 2, parent_file_id: 1
```

## Chat Integration

### Automatic Chat Creation

When admin assigns a lawyer via Dcat Admin:

1. Chat room is created with consultation title
2. User (consultation creator) is added as participant
3. Lawyer is added as participant
4. System message is sent: "Consultation assigned to [Lawyer Name]"
5. Consultation record is updated with `chat_id`

### Accessing Chat

From consultation detail page, if `chat_id` exists, user can click "Open Chat" button.

## Permission System

### User Types & Permissions

- **company_admin / expert**: Can create consultations
- **lawyer**: Cannot create consultations, only respond to assigned ones

### Access Control

- Users can only view their own consultations
- Lawyers can only view assigned consultations
- Admins can view all consultations

## Admin Panel Features

### Consultation Grid

- Filter by status, topic, priority
- Quick search by title/description
- Batch operations disabled (safety)
- Export functionality

### Lawyer Assignment

1. Click "Assign Lawyer" on pending consultation
2. Enter lawyer user ID
3. System validates lawyer role
4. Chat room created automatically
5. Status changed to "assigned"

### File Management

- View all files in consultation detail
- Download files
- See version history
- File preview (if browser supports)

## Testing Workflow

### Complete Flow Test

1. **Create Consultation** (as company_admin/expert)
   ```
   - Login as non-lawyer user
   - Navigate to /pages/consultation/create
   - Fill form and upload files
   - Submit
   ```

2. **Assign Lawyer** (as admin)
   ```
   - Login to Dcat Admin (/admin)
   - Navigate to Consultations
   - Find pending consultation
   - Click "Assign Lawyer"
   - Enter lawyer user ID
   - Verify chat room created
   ```

3. **Access Chat** (as user)
   ```
   - View consultation detail
   - Click "Open Chat"
   - Verify chat opens with lawyer
   - Verify system message exists
   ```

4. **Upload File Version**
   ```
   - In consultation detail, upload file with same name
   - Verify version incremented
   - Check version history
   ```

## Troubleshooting

### Common Issues

1. **Chat not created on assignment**
   - Check if admin user exists in users table
   - Verify lawyer has correct user_type
   - Check ConsultationService logs

2. **File upload fails**
   - Verify storage link exists
   - Check file size against limit
   - Validate file extension

3. **Permission denied errors**
   - Verify JWT token is valid
   - Check user_type matches requirements
   - Ensure user has access to consultation

## Next Steps

1. Run migrations and seeders
2. Configure environment variables
3. Test complete workflow
4. Deploy frontend and backend together
5. Monitor logs for any issues

## Support

For issues or questions, refer to:
- `IMPLEMENTATION_CHECKLIST.md`
- `CHAT_ADMIN_SUMMARY.md`
- Laravel/Dcat Admin documentation

