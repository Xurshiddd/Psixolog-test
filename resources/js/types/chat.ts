export interface ChatUser {
  id: number
  name: string
  picture?: string | null
}

export interface ChatMessage {
  id: number
  conversation_id: number
  sender_id: number
  body: string
  created_at: string
  updated_at: string
  sender?: ChatUser
  is_sending?: boolean // For optimistic UI
}

export interface ConversationListItem {
  id: number
  title: string
  avatar?: string | null
  subtitle?: string
  last_message_at?: string
  unread: number
  user_id?: number // The other participant's ID
}

export interface Conversation {
  id: number
  title?: string
  users: ChatUser[]
  pivot?: {
    last_read_at: string | null
  }
}
