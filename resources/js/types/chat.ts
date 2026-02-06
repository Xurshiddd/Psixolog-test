export type ChatUser = { id: number; name: string }

export type ChatMessage = {
  id: number | string
  conversation_id: number
  body: string
  created_at: string
  sender: ChatUser
}

export type ConversationListItem = {
  id: number
  title: string | null
  users: ChatUser[]
  last_message: null | { body: string; created_at: string; sender_id: number }
  last_read_at: string | null
}
