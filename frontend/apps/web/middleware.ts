import { auth } from '@/auth'
import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

const publicPaths = ['/login', '/signup', '/api/auth']

export default auth(async (req: NextRequest & { auth: unknown }) => {
  const { pathname } = req.nextUrl

  if (pathname.startsWith('/api/')) {
    return NextResponse.next()
  }

  const isPublic = publicPaths.some((p) => pathname.startsWith(p))

  if (!isPublic && !req.auth) {
    const loginUrl = new URL('/login', req.url)
    loginUrl.searchParams.set('redirect', pathname)
    return NextResponse.redirect(loginUrl)
  }

  return NextResponse.next()
})

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
}
