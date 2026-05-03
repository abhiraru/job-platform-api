# Job Platform API

A modular Laravel API for a job platform with candidate profiles, recruiter job posting, applications, and AI-powered job matching.

## Tech Stack

- PHP 8.3+
- Laravel 13
- Laravel Sanctum for API authentication
- Eloquent ORM and Laravel migrations
- PHPUnit for automated tests
- JSON:API-style API responses for newer modules
- OpenAI Chat Completions API over Laravel HTTP client
- SQLite/MySQL/PostgreSQL compatible Laravel database layer

## Main Features

- User registration, login, logout, and token-based auth
- Candidate profile creation and updates
- Resume upload and public resume URL generation
- Recruiter job CRUD
- Paginated, filterable, sortable job listing
- Candidate job applications
- Recruiter applicant review
- Application status updates: `applied`, `shortlisted`, `rejected`
- AI skill matching between candidate profile skills and job requirements
- Cached AI match results to avoid repeated OpenAI calls
- Local fallback matching if OpenAI fails or returns invalid data

## Modules

The application is organized into feature modules under `modules/`:

- `Profile`: candidate profile management, resume upload, profile resources
- `Jobs`: job posts, recruiter ownership, filtering, sorting, pagination
- `Applications`: candidates applying to jobs, recruiter applicant review, status updates
- `AI`: OpenAI-backed job match scoring with cached results and fallback matching

## Authentication

Authentication uses Laravel Sanctum bearer tokens.

Include this header for authenticated endpoints:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/vnd.api+json
```

## Important API Endpoints

### Auth

```http
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
```

### Profile

```http
GET  /api/profile/me
POST /api/profile/me    # use _method=PATCH for multipart resume upload
GET  /api/profiles/{user}
```

Resume upload uses `multipart/form-data`:

```text
_method = PATCH
data[type] = profiles
data[attributes][headline] = Backend Engineer
resume = File
```

### Jobs

```http
GET    /api/jobs
GET    /api/jobs/{job}
POST   /api/jobs
DELETE /api/jobs/{job}
```

Only users with the `employer` role can create jobs. Only the owning recruiter can delete a job.

Example create job request:

```json
{
  "data": {
    "type": "jobs",
    "attributes": {
      "title": "Senior Laravel Engineer",
      "description": "Build APIs for a hiring platform.",
      "skills_required": ["Laravel", "MySQL", "Redis"],
      "location": "Chennai, India",
      "job_type": "full-time",
      "salary_range": "12L-18L"
    }
  }
}
```

Jobs support filtering, pagination, and sorting:

```http
GET /api/jobs?filter[job_type]=full-time&filter[skill]=Laravel&page[size]=10&page[number]=1&sort=-created_at
```

Supported job sorts:

```text
created_at, -created_at, title, -title, location, -location, job_type, -job_type, salary_range, -salary_range
```

### Applications

```http
POST /api/apply
GET  /api/my-applications
GET  /api/jobs/{job}/applications
POST /api/applications/{application}/status
```

Candidate apply request:

```json
{
  "job_id": 1,
  "cover_letter": "I am interested in this role"
}
```

Recruiter status update request:

```json
{
  "status": "shortlisted"
}
```

Application lists support filtering, pagination, and sorting:

```http
GET /api/my-applications?filter[status]=applied&page[size]=10&page[number]=1&sort=-created_at
GET /api/jobs/1/applications?filter[candidate]=Jane&page[size]=10&page[number]=1&sort=status
```

### AI Matching

```http
GET /api/ai/match/{job}
```

The AI match compares:

- candidate profile `skills`
- job `skills_required`

Example response:

```json
{
  "data": {
    "type": "ai-matches",
    "attributes": {
      "match_score": 78,
      "missing_skills": ["Docker", "AWS"],
      "summary": "Strong backend developer, lacks cloud exposure",
      "source": "openai"
    }
  }
}
```

If OpenAI fails, the API returns a reliable local fallback result with:

```json
{
  "source": "fallback"
}
```

## Environment Variables

Copy `.env.example` to `.env` and configure:

```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
OPENAI_API_KEY=your_api_key_here
OPENAI_MODEL=gpt-4o-mini
```

`OPENAI_MODEL` is optional and defaults to `gpt-4o-mini`.

## Local Setup

Install dependencies:

```bash
composer install
npm install
```

Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Create the public storage link for uploaded resumes:

```bash
php artisan storage:link
```

Start the API:

```bash
php artisan serve
```

Run tests:

```bash
php artisan test
```

## Database Tables

Main project tables include:

- `users`
- `profiles`
- `job_posts`
- `job_applications`
- `ai_matches`
- `personal_access_tokens`

Laravel queue tables may also exist, including `jobs`, `job_batches`, and `failed_jobs`.

## Notes

- The job post table is named `job_posts` because Laravel already uses a `jobs` table for queues.
- Profile and job APIs use JSON:API-style resource documents.
- Resume URLs are generated by the server after upload; clients should upload a `resume` file instead of sending `resume_url`.
- AI match results are cached per user/job skill snapshot and recalculated if candidate skills or job skills change.
