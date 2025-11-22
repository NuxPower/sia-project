/*
 Navicat Premium Dump SQL

 Source Server         : klema
 Source Server Type    : PostgreSQL
 Source Server Version : 180001 (180001)
 Source Host           : localhost:5432
 Source Catalog        : klema
 Source Schema         : public

 Target Server Type    : PostgreSQL
 Target Server Version : 180001 (180001)
 File Encoding         : 65001

 Date: 23/11/2025 07:31:41
*/


-- ----------------------------
-- Sequence structure for activities_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."activities_id_seq";
CREATE SEQUENCE "public"."activities_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."activities_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for alerts_alert_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."alerts_alert_id_seq";
CREATE SEQUENCE "public"."alerts_alert_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."alerts_alert_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for exports_export_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."exports_export_id_seq";
CREATE SEQUENCE "public"."exports_export_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."exports_export_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for failed_jobs_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."failed_jobs_id_seq";
CREATE SEQUENCE "public"."failed_jobs_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."failed_jobs_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for farm_points_point_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."farm_points_point_id_seq";
CREATE SEQUENCE "public"."farm_points_point_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."farm_points_point_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for farms_farm_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."farms_farm_id_seq";
CREATE SEQUENCE "public"."farms_farm_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."farms_farm_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for forecasts_forecast_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."forecasts_forecast_id_seq";
CREATE SEQUENCE "public"."forecasts_forecast_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."forecasts_forecast_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for jobs_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."jobs_id_seq";
CREATE SEQUENCE "public"."jobs_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."jobs_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for migrations_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."migrations_id_seq";
CREATE SEQUENCE "public"."migrations_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 2147483647
START 1
CACHE 1;
ALTER SEQUENCE "public"."migrations_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for personal_access_tokens_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."personal_access_tokens_id_seq";
CREATE SEQUENCE "public"."personal_access_tokens_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."personal_access_tokens_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for user_settings_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."user_settings_id_seq";
CREATE SEQUENCE "public"."user_settings_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."user_settings_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for users_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."users_id_seq";
CREATE SEQUENCE "public"."users_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."users_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Sequence structure for weather_data_weather_id_seq
-- ----------------------------
DROP SEQUENCE IF EXISTS "public"."weather_data_weather_id_seq";
CREATE SEQUENCE "public"."weather_data_weather_id_seq" 
INCREMENT 1
MINVALUE  1
MAXVALUE 9223372036854775807
START 1
CACHE 1;
ALTER SEQUENCE "public"."weather_data_weather_id_seq" OWNER TO "postgres";

-- ----------------------------
-- Table structure for activities
-- ----------------------------
DROP TABLE IF EXISTS "public"."activities";
CREATE TABLE "public"."activities" (
  "id" int8 NOT NULL DEFAULT nextval('activities_id_seq'::regclass),
  "user_id" int8 NOT NULL,
  "activity_type" varchar(100) COLLATE "pg_catalog"."default" NOT NULL,
  "field" varchar(100) COLLATE "pg_catalog"."default" NOT NULL,
  "start_date" date NOT NULL,
  "end_date" date,
  "weather_warning" text COLLATE "pg_catalog"."default",
  "status" varchar(255) COLLATE "pg_catalog"."default" NOT NULL DEFAULT 'pending'::character varying,
  "notes" text COLLATE "pg_catalog"."default",
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;
ALTER TABLE "public"."activities" OWNER TO "postgres";

-- ----------------------------
-- Table structure for alerts
-- ----------------------------
DROP TABLE IF EXISTS "public"."alerts";
CREATE TABLE "public"."alerts" (
  "alert_id" int8 NOT NULL DEFAULT nextval('alerts_alert_id_seq'::regclass),
  "farm_id" int8 NOT NULL,
  "alert_type" varchar(50) COLLATE "pg_catalog"."default",
  "message" text COLLATE "pg_catalog"."default" NOT NULL,
  "issued_at" timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "resolved" bool NOT NULL DEFAULT false,
  "is_system_generated" bool NOT NULL DEFAULT false,
  "automation_key" varchar(255) COLLATE "pg_catalog"."default"
)
;
ALTER TABLE "public"."alerts" OWNER TO "postgres";

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS "public"."cache";
CREATE TABLE "public"."cache" (
  "key" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "value" text COLLATE "pg_catalog"."default" NOT NULL,
  "expiration" int4 NOT NULL
)
;
ALTER TABLE "public"."cache" OWNER TO "postgres";

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS "public"."cache_locks";
CREATE TABLE "public"."cache_locks" (
  "key" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "owner" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "expiration" int4 NOT NULL
)
;
ALTER TABLE "public"."cache_locks" OWNER TO "postgres";

-- ----------------------------
-- Table structure for exports
-- ----------------------------
DROP TABLE IF EXISTS "public"."exports";
CREATE TABLE "public"."exports" (
  "export_id" int8 NOT NULL DEFAULT nextval('exports_export_id_seq'::regclass),
  "user_id" int8 NOT NULL,
  "file_name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "file_path" text COLLATE "pg_catalog"."default" NOT NULL,
  "created_at" timestamp(0),
  "updated_at" timestamp(0),
  "disk" varchar(50) COLLATE "pg_catalog"."default" NOT NULL DEFAULT 'local'::character varying
)
;
ALTER TABLE "public"."exports" OWNER TO "postgres";

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS "public"."failed_jobs";
CREATE TABLE "public"."failed_jobs" (
  "id" int8 NOT NULL DEFAULT nextval('failed_jobs_id_seq'::regclass),
  "uuid" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "connection" text COLLATE "pg_catalog"."default" NOT NULL,
  "queue" text COLLATE "pg_catalog"."default" NOT NULL,
  "payload" text COLLATE "pg_catalog"."default" NOT NULL,
  "exception" text COLLATE "pg_catalog"."default" NOT NULL,
  "failed_at" timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
)
;
ALTER TABLE "public"."failed_jobs" OWNER TO "postgres";

-- ----------------------------
-- Table structure for farm_points
-- ----------------------------
DROP TABLE IF EXISTS "public"."farm_points";
CREATE TABLE "public"."farm_points" (
  "point_id" int8 NOT NULL DEFAULT nextval('farm_points_point_id_seq'::regclass),
  "farm_id" int8 NOT NULL,
  "label" varchar(100) COLLATE "pg_catalog"."default" NOT NULL,
  "latitude" numeric(9,6) NOT NULL,
  "longitude" numeric(9,6) NOT NULL,
  "point_type" varchar(50) COLLATE "pg_catalog"."default",
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;
ALTER TABLE "public"."farm_points" OWNER TO "postgres";

-- ----------------------------
-- Table structure for farms
-- ----------------------------
DROP TABLE IF EXISTS "public"."farms";
CREATE TABLE "public"."farms" (
  "farm_id" int8 NOT NULL DEFAULT nextval('farms_farm_id_seq'::regclass),
  "user_id" int8 NOT NULL,
  "farm_name" varchar(100) COLLATE "pg_catalog"."default" NOT NULL,
  "latitude" numeric(9,6) NOT NULL,
  "longitude" numeric(9,6) NOT NULL,
  "created_at" timestamp(0),
  "updated_at" timestamp(0),
  "size_hectares" numeric(10,2),
  "soil_type" varchar(100) COLLATE "pg_catalog"."default",
  "description" text COLLATE "pg_catalog"."default",
  "boundary_geojson" json
)
;
ALTER TABLE "public"."farms" OWNER TO "postgres";

-- ----------------------------
-- Table structure for forecasts
-- ----------------------------
DROP TABLE IF EXISTS "public"."forecasts";
CREATE TABLE "public"."forecasts" (
  "forecast_id" int8 NOT NULL DEFAULT nextval('forecasts_forecast_id_seq'::regclass),
  "farm_id" int8,
  "location_name" varchar(150) COLLATE "pg_catalog"."default",
  "latitude" numeric(9,6),
  "longitude" numeric(9,6),
  "forecast_date" date NOT NULL,
  "temp_max" numeric(5,2),
  "temp_min" numeric(5,2),
  "condition" varchar(100) COLLATE "pg_catalog"."default",
  "condition_icon" varchar(12) COLLATE "pg_catalog"."default",
  "description" varchar(255) COLLATE "pg_catalog"."default",
  "precip_probability" numeric(5,2),
  "sunrise" int4,
  "sunset" int4,
  "timezone_offset" int4 NOT NULL DEFAULT 0,
  "hourly_data" json,
  "cached_at" timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "expires_at" timestamp(0) NOT NULL
)
;
ALTER TABLE "public"."forecasts" OWNER TO "postgres";

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS "public"."job_batches";
CREATE TABLE "public"."job_batches" (
  "id" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "total_jobs" int4 NOT NULL,
  "pending_jobs" int4 NOT NULL,
  "failed_jobs" int4 NOT NULL,
  "failed_job_ids" text COLLATE "pg_catalog"."default" NOT NULL,
  "options" text COLLATE "pg_catalog"."default",
  "cancelled_at" int4,
  "created_at" int4 NOT NULL,
  "finished_at" int4
)
;
ALTER TABLE "public"."job_batches" OWNER TO "postgres";

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS "public"."jobs";
CREATE TABLE "public"."jobs" (
  "id" int8 NOT NULL DEFAULT nextval('jobs_id_seq'::regclass),
  "queue" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "payload" text COLLATE "pg_catalog"."default" NOT NULL,
  "attempts" int2 NOT NULL,
  "reserved_at" int4,
  "available_at" int4 NOT NULL,
  "created_at" int4 NOT NULL
)
;
ALTER TABLE "public"."jobs" OWNER TO "postgres";

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS "public"."migrations";
CREATE TABLE "public"."migrations" (
  "id" int4 NOT NULL DEFAULT nextval('migrations_id_seq'::regclass),
  "migration" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "batch" int4 NOT NULL
)
;
ALTER TABLE "public"."migrations" OWNER TO "postgres";

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS "public"."password_reset_tokens";
CREATE TABLE "public"."password_reset_tokens" (
  "email" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "token" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "created_at" timestamp(0)
)
;
ALTER TABLE "public"."password_reset_tokens" OWNER TO "postgres";

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS "public"."personal_access_tokens";
CREATE TABLE "public"."personal_access_tokens" (
  "id" int8 NOT NULL DEFAULT nextval('personal_access_tokens_id_seq'::regclass),
  "tokenable_type" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "tokenable_id" int8 NOT NULL,
  "name" text COLLATE "pg_catalog"."default" NOT NULL,
  "token" varchar(64) COLLATE "pg_catalog"."default" NOT NULL,
  "abilities" text COLLATE "pg_catalog"."default",
  "last_used_at" timestamp(0),
  "expires_at" timestamp(0),
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;
ALTER TABLE "public"."personal_access_tokens" OWNER TO "postgres";

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS "public"."sessions";
CREATE TABLE "public"."sessions" (
  "id" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "user_id" int8,
  "ip_address" varchar(45) COLLATE "pg_catalog"."default",
  "user_agent" text COLLATE "pg_catalog"."default",
  "payload" text COLLATE "pg_catalog"."default" NOT NULL,
  "last_activity" int4 NOT NULL
)
;
ALTER TABLE "public"."sessions" OWNER TO "postgres";

-- ----------------------------
-- Table structure for user_settings
-- ----------------------------
DROP TABLE IF EXISTS "public"."user_settings";
CREATE TABLE "public"."user_settings" (
  "id" int8 NOT NULL DEFAULT nextval('user_settings_id_seq'::regclass),
  "user_id" int8 NOT NULL,
  "settings" json,
  "created_at" timestamp(0),
  "updated_at" timestamp(0)
)
;
ALTER TABLE "public"."user_settings" OWNER TO "postgres";
COMMENT ON COLUMN "public"."user_settings"."settings" IS 'JSON object containing user preferences';

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS "public"."users";
CREATE TABLE "public"."users" (
  "id" int8 NOT NULL DEFAULT nextval('users_id_seq'::regclass),
  "name" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "email" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "email_verified_at" timestamp(0),
  "password" varchar(255) COLLATE "pg_catalog"."default" NOT NULL,
  "remember_token" varchar(100) COLLATE "pg_catalog"."default",
  "created_at" timestamp(0),
  "updated_at" timestamp(0),
  "role" varchar(255) COLLATE "pg_catalog"."default" NOT NULL DEFAULT 'farmer'::character varying
)
;
ALTER TABLE "public"."users" OWNER TO "postgres";

-- ----------------------------
-- Table structure for weather_data
-- ----------------------------
DROP TABLE IF EXISTS "public"."weather_data";
CREATE TABLE "public"."weather_data" (
  "weather_id" int8 NOT NULL DEFAULT nextval('weather_data_weather_id_seq'::regclass),
  "farm_id" int8,
  "temperature" numeric(5,2),
  "humidity" numeric(5,2),
  "rainfall" numeric(5,2),
  "wind_speed" numeric(5,2),
  "condition" varchar(100) COLLATE "pg_catalog"."default",
  "recorded_at" timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "location_name" varchar(150) COLLATE "pg_catalog"."default",
  "latitude" numeric(9,6),
  "longitude" numeric(9,6),
  "condition_icon" varchar(12) COLLATE "pg_catalog"."default"
)
;
ALTER TABLE "public"."weather_data" OWNER TO "postgres";

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."activities_id_seq"
OWNED BY "public"."activities"."id";
SELECT setval('"public"."activities_id_seq"', 5, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."alerts_alert_id_seq"
OWNED BY "public"."alerts"."alert_id";
SELECT setval('"public"."alerts_alert_id_seq"', 8, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."exports_export_id_seq"
OWNED BY "public"."exports"."export_id";
SELECT setval('"public"."exports_export_id_seq"', 17, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."failed_jobs_id_seq"
OWNED BY "public"."failed_jobs"."id";
SELECT setval('"public"."failed_jobs_id_seq"', 1, false);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."farm_points_point_id_seq"
OWNED BY "public"."farm_points"."point_id";
SELECT setval('"public"."farm_points_point_id_seq"', 5, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."farms_farm_id_seq"
OWNED BY "public"."farms"."farm_id";
SELECT setval('"public"."farms_farm_id_seq"', 4, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."forecasts_forecast_id_seq"
OWNED BY "public"."forecasts"."forecast_id";
SELECT setval('"public"."forecasts_forecast_id_seq"', 9, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."jobs_id_seq"
OWNED BY "public"."jobs"."id";
SELECT setval('"public"."jobs_id_seq"', 71, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."migrations_id_seq"
OWNED BY "public"."migrations"."id";
SELECT setval('"public"."migrations_id_seq"', 14, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."personal_access_tokens_id_seq"
OWNED BY "public"."personal_access_tokens"."id";
SELECT setval('"public"."personal_access_tokens_id_seq"', 99, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."user_settings_id_seq"
OWNED BY "public"."user_settings"."id";
SELECT setval('"public"."user_settings_id_seq"', 1, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."users_id_seq"
OWNED BY "public"."users"."id";
SELECT setval('"public"."users_id_seq"', 13, true);

-- ----------------------------
-- Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "public"."weather_data_weather_id_seq"
OWNED BY "public"."weather_data"."weather_id";
SELECT setval('"public"."weather_data_weather_id_seq"', 1211, true);

-- ----------------------------
-- Checks structure for table activities
-- ----------------------------
ALTER TABLE "public"."activities" ADD CONSTRAINT "activities_status_check" CHECK (status::text = ANY (ARRAY['pending'::character varying::text, 'in_progress'::character varying::text, 'completed'::character varying::text, 'cancelled'::character varying::text]));

-- ----------------------------
-- Primary Key structure for table activities
-- ----------------------------
ALTER TABLE "public"."activities" ADD CONSTRAINT "activities_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table alerts
-- ----------------------------
CREATE INDEX "alerts_automation_key_index" ON "public"."alerts" USING btree (
  "automation_key" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table alerts
-- ----------------------------
ALTER TABLE "public"."alerts" ADD CONSTRAINT "alerts_pkey" PRIMARY KEY ("alert_id");

-- ----------------------------
-- Primary Key structure for table cache
-- ----------------------------
ALTER TABLE "public"."cache" ADD CONSTRAINT "cache_pkey" PRIMARY KEY ("key");

-- ----------------------------
-- Primary Key structure for table cache_locks
-- ----------------------------
ALTER TABLE "public"."cache_locks" ADD CONSTRAINT "cache_locks_pkey" PRIMARY KEY ("key");

-- ----------------------------
-- Primary Key structure for table exports
-- ----------------------------
ALTER TABLE "public"."exports" ADD CONSTRAINT "exports_pkey" PRIMARY KEY ("export_id");

-- ----------------------------
-- Uniques structure for table failed_jobs
-- ----------------------------
ALTER TABLE "public"."failed_jobs" ADD CONSTRAINT "failed_jobs_uuid_unique" UNIQUE ("uuid");

-- ----------------------------
-- Primary Key structure for table failed_jobs
-- ----------------------------
ALTER TABLE "public"."failed_jobs" ADD CONSTRAINT "failed_jobs_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Primary Key structure for table farm_points
-- ----------------------------
ALTER TABLE "public"."farm_points" ADD CONSTRAINT "farm_points_pkey" PRIMARY KEY ("point_id");

-- ----------------------------
-- Primary Key structure for table farms
-- ----------------------------
ALTER TABLE "public"."farms" ADD CONSTRAINT "farms_pkey" PRIMARY KEY ("farm_id");

-- ----------------------------
-- Indexes structure for table forecasts
-- ----------------------------
CREATE INDEX "forecast_coords_date_idx" ON "public"."forecasts" USING btree (
  "latitude" "pg_catalog"."numeric_ops" ASC NULLS LAST,
  "longitude" "pg_catalog"."numeric_ops" ASC NULLS LAST,
  "forecast_date" "pg_catalog"."date_ops" ASC NULLS LAST,
  "expires_at" "pg_catalog"."timestamp_ops" ASC NULLS LAST
);
CREATE INDEX "forecast_expires_idx" ON "public"."forecasts" USING btree (
  "expires_at" "pg_catalog"."timestamp_ops" ASC NULLS LAST
);
CREATE INDEX "forecast_farm_date_idx" ON "public"."forecasts" USING btree (
  "farm_id" "pg_catalog"."int8_ops" ASC NULLS LAST,
  "forecast_date" "pg_catalog"."date_ops" ASC NULLS LAST,
  "expires_at" "pg_catalog"."timestamp_ops" ASC NULLS LAST
);
CREATE INDEX "forecast_location_date_idx" ON "public"."forecasts" USING btree (
  "location_name" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST,
  "forecast_date" "pg_catalog"."date_ops" ASC NULLS LAST,
  "expires_at" "pg_catalog"."timestamp_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table forecasts
-- ----------------------------
ALTER TABLE "public"."forecasts" ADD CONSTRAINT "forecasts_pkey" PRIMARY KEY ("forecast_id");

-- ----------------------------
-- Primary Key structure for table job_batches
-- ----------------------------
ALTER TABLE "public"."job_batches" ADD CONSTRAINT "job_batches_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table jobs
-- ----------------------------
CREATE INDEX "jobs_queue_index" ON "public"."jobs" USING btree (
  "queue" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table jobs
-- ----------------------------
ALTER TABLE "public"."jobs" ADD CONSTRAINT "jobs_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Primary Key structure for table migrations
-- ----------------------------
ALTER TABLE "public"."migrations" ADD CONSTRAINT "migrations_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Primary Key structure for table password_reset_tokens
-- ----------------------------
ALTER TABLE "public"."password_reset_tokens" ADD CONSTRAINT "password_reset_tokens_pkey" PRIMARY KEY ("email");

-- ----------------------------
-- Indexes structure for table personal_access_tokens
-- ----------------------------
CREATE INDEX "personal_access_tokens_expires_at_index" ON "public"."personal_access_tokens" USING btree (
  "expires_at" "pg_catalog"."timestamp_ops" ASC NULLS LAST
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" ON "public"."personal_access_tokens" USING btree (
  "tokenable_type" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST,
  "tokenable_id" "pg_catalog"."int8_ops" ASC NULLS LAST
);

-- ----------------------------
-- Uniques structure for table personal_access_tokens
-- ----------------------------
ALTER TABLE "public"."personal_access_tokens" ADD CONSTRAINT "personal_access_tokens_token_unique" UNIQUE ("token");

-- ----------------------------
-- Primary Key structure for table personal_access_tokens
-- ----------------------------
ALTER TABLE "public"."personal_access_tokens" ADD CONSTRAINT "personal_access_tokens_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table sessions
-- ----------------------------
CREATE INDEX "sessions_last_activity_index" ON "public"."sessions" USING btree (
  "last_activity" "pg_catalog"."int4_ops" ASC NULLS LAST
);
CREATE INDEX "sessions_user_id_index" ON "public"."sessions" USING btree (
  "user_id" "pg_catalog"."int8_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table sessions
-- ----------------------------
ALTER TABLE "public"."sessions" ADD CONSTRAINT "sessions_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table user_settings
-- ----------------------------
CREATE INDEX "user_settings_user_id_index" ON "public"."user_settings" USING btree (
  "user_id" "pg_catalog"."int8_ops" ASC NULLS LAST
);

-- ----------------------------
-- Uniques structure for table user_settings
-- ----------------------------
ALTER TABLE "public"."user_settings" ADD CONSTRAINT "user_settings_user_id_unique" UNIQUE ("user_id");

-- ----------------------------
-- Primary Key structure for table user_settings
-- ----------------------------
ALTER TABLE "public"."user_settings" ADD CONSTRAINT "user_settings_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Uniques structure for table users
-- ----------------------------
ALTER TABLE "public"."users" ADD CONSTRAINT "users_email_unique" UNIQUE ("email");

-- ----------------------------
-- Checks structure for table users
-- ----------------------------
ALTER TABLE "public"."users" ADD CONSTRAINT "users_role_check" CHECK (role::text = ANY (ARRAY['admin'::character varying::text, 'farmer'::character varying::text]));

-- ----------------------------
-- Primary Key structure for table users
-- ----------------------------
ALTER TABLE "public"."users" ADD CONSTRAINT "users_pkey" PRIMARY KEY ("id");

-- ----------------------------
-- Indexes structure for table weather_data
-- ----------------------------
CREATE INDEX "weather_coords_recorded_idx" ON "public"."weather_data" USING btree (
  "latitude" "pg_catalog"."numeric_ops" ASC NULLS LAST,
  "longitude" "pg_catalog"."numeric_ops" ASC NULLS LAST,
  "recorded_at" "pg_catalog"."timestamp_ops" ASC NULLS LAST
);
CREATE INDEX "weather_location_recorded_idx" ON "public"."weather_data" USING btree (
  "location_name" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST,
  "recorded_at" "pg_catalog"."timestamp_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table weather_data
-- ----------------------------
ALTER TABLE "public"."weather_data" ADD CONSTRAINT "weather_data_pkey" PRIMARY KEY ("weather_id");

-- ----------------------------
-- Foreign Keys structure for table activities
-- ----------------------------
ALTER TABLE "public"."activities" ADD CONSTRAINT "activities_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "public"."users" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table alerts
-- ----------------------------
ALTER TABLE "public"."alerts" ADD CONSTRAINT "alerts_farm_id_foreign" FOREIGN KEY ("farm_id") REFERENCES "public"."farms" ("farm_id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table exports
-- ----------------------------
ALTER TABLE "public"."exports" ADD CONSTRAINT "exports_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "public"."users" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table farm_points
-- ----------------------------
ALTER TABLE "public"."farm_points" ADD CONSTRAINT "farm_points_farm_id_foreign" FOREIGN KEY ("farm_id") REFERENCES "public"."farms" ("farm_id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table farms
-- ----------------------------
ALTER TABLE "public"."farms" ADD CONSTRAINT "farms_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "public"."users" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table forecasts
-- ----------------------------
ALTER TABLE "public"."forecasts" ADD CONSTRAINT "forecasts_farm_id_foreign" FOREIGN KEY ("farm_id") REFERENCES "public"."farms" ("farm_id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table user_settings
-- ----------------------------
ALTER TABLE "public"."user_settings" ADD CONSTRAINT "user_settings_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "public"."users" ("id") ON DELETE CASCADE ON UPDATE NO ACTION;

-- ----------------------------
-- Foreign Keys structure for table weather_data
-- ----------------------------
ALTER TABLE "public"."weather_data" ADD CONSTRAINT "weather_data_farm_id_foreign" FOREIGN KEY ("farm_id") REFERENCES "public"."farms" ("farm_id") ON DELETE CASCADE ON UPDATE NO ACTION;
