interface MailpitMessage {
  ID: string
  From: { Address: string; Name: string }
  To: Array<{ Address: string; Name: string }>
  Subject: string
  Created: string
  Text: string
  HTML: string
}

interface MailpitResponse {
  total: number
  messages: MailpitMessage[]
}

export class MailpitHelper {
  private baseUrl: string

  constructor(baseUrl?: string) {
    this.baseUrl = baseUrl || process.env.MAILPIT_URL || 'http://localhost:8026'
  }

  async getMessages(): Promise<MailpitMessage[]> {
    const response = await fetch(`${this.baseUrl}/api/v1/messages`)
    const data: MailpitResponse = await response.json()
    return data.messages || []
  }

  async getMessageById(id: string): Promise<MailpitMessage | null> {
    try {
      const response = await fetch(`${this.baseUrl}/api/v1/message/${id}`)
      if (!response.ok) return null
      return await response.json()
    } catch {
      return null
    }
  }

  async getLatestMessage(): Promise<MailpitMessage | null> {
    const messages = await this.getMessages()
    if (messages.length === 0) return null
    return this.getMessageById(messages[0].ID)
  }

  async getMessageByRecipient(email: string): Promise<MailpitMessage | null> {
    const messages = await this.getMessages()
    const found = messages.find((msg) =>
      msg.To.some((to) => to.Address.toLowerCase() === email.toLowerCase())
    )
    if (!found) return null
    return this.getMessageById(found.ID)
  }

  async getMessageBySubject(subject: string): Promise<MailpitMessage | null> {
    const messages = await this.getMessages()
    const found = messages.find((msg) => msg.Subject.includes(subject))
    if (!found) return null
    return this.getMessageById(found.ID)
  }

  extractLink(message: MailpitMessage, pattern: RegExp): string | null {
    const match = message.HTML.match(pattern) || message.Text.match(pattern)
    return match ? match[1] || match[0] : null
  }

  extractToken(message: MailpitMessage, urlPattern: string): string | null {
    const urlRegex = new RegExp(
      `(https?:\\/\\/[^\\s"'<>]+\\/${urlPattern}\\/[^\\s"'<>]+)`,
      'i'
    )
    const url = this.extractLink(message, urlRegex)
    if (!url) return null

    const tokenRegex = new RegExp(`\\/${urlPattern}\\/([^\\s"'<>]+)`)
    const tokenMatch = url.match(tokenRegex)
    return tokenMatch ? tokenMatch[1] : null
  }

  async clearMessages(): Promise<void> {
    await fetch(`${this.baseUrl}/api/v1/messages`, { method: 'DELETE' })
  }

  async waitForMessage(
    predicate: (msg: MailpitMessage) => boolean,
    timeout = 10000
  ): Promise<MailpitMessage | null> {
    const startTime = Date.now()

    while (Date.now() - startTime < timeout) {
      const messages = await this.getMessages()
      const found = messages.find(predicate)
      if (found) return this.getMessageById(found.ID)
      await new Promise((resolve) => setTimeout(resolve, 500))
    }

    return null
  }
}
