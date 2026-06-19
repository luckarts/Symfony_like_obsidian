export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-panel flex flex-col items-center justify-center px-4">
      <div className="flex-1 flex items-center justify-center w-full">
        {children}
      </div>
      <footer className="w-full py-6 flex flex-col items-center gap-3 text-sm text-muted-foreground">
        <p>©2026 TaskManager. All Rights Reserved.</p>
        <nav className="flex gap-6">
          <a href="#" className="hover:text-foreground transition-colors">Support</a>
          <a href="#" className="hover:text-foreground transition-colors">License</a>
          <a href="#" className="hover:text-foreground transition-colors">Terms of Use</a>
        </nav>
      </footer>
    </div>
  )
}
