import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"
import { UserType } from "@/types/enums"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Check if a user has admin permissions (admin or owner).
 */
export function hasAdminPermissions(userType: string): boolean {
  return userType === UserType.ADMIN || userType === UserType.OWNER
}
