import { NextResponse } from "next/server"
import { requireAdmin } from "@/lib/admin-helpers"

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const admin = await requireAdmin()
  if (!admin.ok) {
    return NextResponse.json({ message: admin.error }, { status: admin.status })
  }

  const { id } = await params
  const attendanceId = Number(id)
  if (!Number.isInteger(attendanceId) || attendanceId <= 0) {
    return new NextResponse("Not Found", { status: 404 })
  }

  const { data: attendance } = await admin.supabaseAdmin
    .from("attendances")
    .select("check_in_photo_path")
    .eq("id", attendanceId)
    .maybeSingle()

  const cleanPath = (attendance?.check_in_photo_path ?? "")
    .trim()
    .replace(/^\/+/, "")

  if (!cleanPath) {
    return new NextResponse("Not Found", { status: 404 })
  }

  const bucket = process.env.SUPABASE_STORAGE_BUCKET || "photos"
  const { data: blob, error } = await admin.supabaseAdmin.storage
    .from(bucket)
    .download(cleanPath)

  if (error || !blob) {
    return new NextResponse("Not Found", { status: 404 })
  }

  const contentType =
    blob.type && blob.type.startsWith("image/") ? blob.type : "image/jpeg"
  const buffer = await blob.arrayBuffer()

  return new NextResponse(buffer, {
    status: 200,
    headers: {
      "Content-Type": contentType,
      "Cache-Control": "private, max-age=3600",
    },
  })
}
