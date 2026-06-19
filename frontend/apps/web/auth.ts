import NextAuth from 'next-auth'
import Credentials from 'next-auth/providers/credentials'

export const { handlers, auth, signIn, signOut } = NextAuth({
  providers: [
    {
      id: 'symfony',
      name: 'Symfony OAuth2',
      type: 'oauth',
      authorization: {
        url: `${process.env.API_URL}/oauth2/authorize`,
        params: { scope: 'openid profile email' },
      },
      token: `${process.env.API_URL}/oauth2/token`,
      userinfo: `${process.env.API_URL}/api/me`,
      clientId: process.env.OAUTH_CLIENT_ID,
      clientSecret: process.env.OAUTH_CLIENT_SECRET,
      profile(profile) {
        return {
          id: profile.id ?? profile.sub,
          name: profile.name ?? `${profile.firstName} ${profile.lastName}`,
          email: profile.email,
        }
      },
    },
    Credentials({
      credentials: {
        email: { label: 'Email', type: 'email' },
        password: { label: 'Password', type: 'password' },
      },
      async authorize(credentials) {
        const tokenRes = await fetch(`${process.env.API_URL}/oauth2/token`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            grant_type: 'password',
            client_id: process.env.OAUTH_CLIENT_ID!,
            client_secret: process.env.OAUTH_CLIENT_SECRET!,
            username: credentials.email as string,
            password: credentials.password as string,
            scope: 'openid profile email',
          }),
        })
        if (!tokenRes.ok) return null
        const { access_token } = await tokenRes.json()
        const userRes = await fetch(`${process.env.API_URL}/api/me`, {
          headers: { Authorization: `Bearer ${access_token}` },
        })
        if (!userRes.ok) return null
        const profile = await userRes.json()
        return {
          id: profile.id ?? profile.sub,
          name: profile.name ?? `${profile.firstName} ${profile.lastName}`,
          email: profile.email,
          accessToken: access_token,
        }
      },
    }),
  ],
  callbacks: {
    async jwt({ token, account, user }) {
      if (account) token.accessToken = account.access_token
      if (user && (user as { accessToken?: string }).accessToken) {
        token.accessToken = (user as { accessToken: string }).accessToken
      }
      return token
    },
    async session({ session, token }) {
      session.accessToken = token.accessToken as string
      return session
    },
  },
  pages: {
    signIn: '/login',
  },
})
