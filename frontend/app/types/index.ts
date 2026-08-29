export interface User {
  id: number
  name: string
  bio: string | null
  avatar: string | null
}

export interface Post {
  id: number
  body: string
  likes_count: number
  replies_count: number
  reposts_count: number
  liked: boolean
  reposted: boolean
  created_at: string
  user: User
}

export const NotificationType = {
  Like:    'like',
  Comment: 'comment',
  Follow:  'follow',
  Mention: 'mention',
  Repost:  'repost',
  Share:   'share',
} as const

export type NotificationTypeValue = typeof NotificationType[keyof typeof NotificationType]

export interface NotificationData {
  type: NotificationTypeValue
  message: string
  actor_id: number
  actor_name: string
  resource_type: string | null
  resource_id: number | null
}

export interface Notification {
  id: string
  data: NotificationData
  read_at: string | null
  created_at: string
}
