export type Json =
  | string
  | number
  | boolean
  | null
  | { [key: string]: Json | undefined }
  | Json[]

export type Database = {
  __InternalSupabase: {
    PostgrestVersion: "14.18"
  }
  public: {
    Tables: {
      attendances: {
        Row: {
          attendance_date: string
          check_in_accuracy: number | null
          check_in_at: string | null
          check_in_distance: number | null
          check_in_latitude: number | null
          check_in_longitude: number | null
          check_in_photo_path: string | null
          check_out_accuracy: number | null
          check_out_at: string | null
          check_out_distance: number | null
          check_out_latitude: number | null
          check_out_longitude: number | null
          created_at: string
          id: number
          is_approved: boolean | null
          location_type: string
          reason: string | null
          status: string
          student_id: number
          updated_at: string
        }
        Insert: {
          attendance_date: string
          check_in_accuracy?: number | null
          check_in_at?: string | null
          check_in_distance?: number | null
          check_in_latitude?: number | null
          check_in_longitude?: number | null
          check_in_photo_path?: string | null
          check_out_accuracy?: number | null
          check_out_at?: string | null
          check_out_distance?: number | null
          check_out_latitude?: number | null
          check_out_longitude?: number | null
          created_at?: string
          id?: never
          is_approved?: boolean | null
          location_type?: string
          reason?: string | null
          status: string
          student_id: number
          updated_at?: string
        }
        Update: {
          attendance_date?: string
          check_in_accuracy?: number | null
          check_in_at?: string | null
          check_in_distance?: number | null
          check_in_latitude?: number | null
          check_in_longitude?: number | null
          check_in_photo_path?: string | null
          check_out_accuracy?: number | null
          check_out_at?: string | null
          check_out_distance?: number | null
          check_out_latitude?: number | null
          check_out_longitude?: number | null
          created_at?: string
          id?: never
          is_approved?: boolean | null
          location_type?: string
          reason?: string | null
          status?: string
          student_id?: number
          updated_at?: string
        }
        Relationships: [
          {
            foreignKeyName: "attendances_student_id_fkey"
            columns: ["student_id"]
            isOneToOne: false
            referencedRelation: "students"
            referencedColumns: ["id"]
          },
        ]
      }
      office_locations: {
        Row: {
          active: boolean
          created_at: string
          id: number
          latitude: number
          longitude: number
          max_accuracy_meters: number
          name: string
          radius_meters: number
          updated_at: string
        }
        Insert: {
          active?: boolean
          created_at?: string
          id?: never
          latitude: number
          longitude: number
          max_accuracy_meters: number
          name: string
          radius_meters: number
          updated_at?: string
        }
        Update: {
          active?: boolean
          created_at?: string
          id?: never
          latitude?: number
          longitude?: number
          max_accuracy_meters?: number
          name?: string
          radius_meters?: number
          updated_at?: string
        }
        Relationships: []
      }
      profiles: {
        Row: {
          created_at: string
          email: string
          id: string
          name: string
          role: string
          updated_at: string
        }
        Insert: {
          created_at?: string
          email: string
          id: string
          name: string
          role?: string
          updated_at?: string
        }
        Update: {
          created_at?: string
          email?: string
          id?: string
          name?: string
          role?: string
          updated_at?: string
        }
        Relationships: []
      }
      students: {
        Row: {
          created_at: string
          end_date: string
          id: number
          major: string
          school: string
          start_date: string
          status: string
          student_code: string
          updated_at: string
          user_id: string
        }
        Insert: {
          created_at?: string
          end_date: string
          id?: never
          major: string
          school: string
          start_date: string
          status?: string
          student_code: string
          updated_at?: string
          user_id: string
        }
        Update: {
          created_at?: string
          end_date?: string
          id?: never
          major?: string
          school?: string
          start_date?: string
          status?: string
          student_code?: string
          updated_at?: string
          user_id?: string
        }
        Relationships: [
          {
            foreignKeyName: "students_user_id_fkey"
            columns: ["user_id"]
            isOneToOne: true
            referencedRelation: "profiles"
            referencedColumns: ["id"]
          },
        ]
      }
    }
    Views: {
      [_ in never]: never
    }
    Functions: {
      get_bucket_storage_stats: { Args: { p_bucket?: string }; Returns: Json }
    }
    Enums: {
      [_ in never]: never
    }
    CompositeTypes: {
      [_ in never]: never
    }
  }
}

export type Tables<T extends keyof Database["public"]["Tables"]> =
  Database["public"]["Tables"][T]["Row"]
export type TablesInsert<T extends keyof Database["public"]["Tables"]> =
  Database["public"]["Tables"][T]["Insert"]
export type TablesUpdate<T extends keyof Database["public"]["Tables"]> =
  Database["public"]["Tables"][T]["Update"]

export type Profile = Tables<"profiles">
export type Student = Tables<"students">
export type OfficeLocation = Tables<"office_locations">
export type Attendance = Tables<"attendances">

export type BucketStorageStats = {
  total_bytes: number
  active_count: number
  active_bytes: number
  temp_count: number
  temp_bytes: number
  orphan_count: number
  orphan_bytes: number
  cleanable_count: number
  cleanable_bytes: number
}
