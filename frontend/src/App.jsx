import { useCallback, useEffect, useMemo, useState } from 'react'
import './App.css'

const API_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000').replace(/\/+$/, '')
const TOKEN_KEY = 'stockroom-api-tokens'

async function request(path, { token, ...options } = {}) {
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  })
  const payload = response.status === 204 ? null : await response.json().catch(() => null)

  if (!response.ok) {
    const error = new Error(payload?.error || `Request failed with status ${response.status}.`)
    error.status = response.status
    throw error
  }
  return payload
}

function readTokens() {
  try {
    return JSON.parse(sessionStorage.getItem(TOKEN_KEY) || 'null')
  } catch {
    sessionStorage.removeItem(TOKEN_KEY)
    return null
  }
}

function App() {
  const [tokens, setTokens] = useState(readTokens)
  const [user, setUser] = useState(null)
  const [products, setProducts] = useState([])
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [modal, setModal] = useState(null)
  const [form, setForm] = useState({ product_name: '', description: '', price: '', quantity: '' })
  const [credentials, setCredentials] = useState({ username: '', password: '' })

  const saveTokens = useCallback((nextTokens) => {
    sessionStorage.setItem(TOKEN_KEY, JSON.stringify(nextTokens))
    setTokens(nextTokens)
  }, [])

  const loadProducts = useCallback(async (accessToken) => {
    const payload = await request('/api/products', { token: accessToken })
    setProducts(payload.products)
  }, [])

  useEffect(() => {
    let active = true
    const restoreSession = async () => {
      const stored = readTokens()
      if (!stored?.refresh_token) {
        if (active) setLoading(false)
        return
      }

      try {
        const payload = await request('/api/auth/refresh', {
          method: 'POST',
          body: JSON.stringify({ refresh_token: stored.refresh_token }),
        })
        if (!active) return
        saveTokens(payload.tokens)
        const profile = await request('/api/auth/me', { token: payload.tokens.access_token })
        await loadProducts(payload.tokens.access_token)
        if (active) setUser(profile.user)
      } catch (err) {
        sessionStorage.removeItem(TOKEN_KEY)
        if (active) {
          setTokens(null)
          setUser(null)
          if (![401, 403].includes(err.status)) {
            setError(err.message)
          }
        }
      } finally {
        if (active) setLoading(false)
      }
    }
    restoreSession()
    return () => { active = false }
  }, [loadProducts, saveTokens])

  const inventoryValue = useMemo(
    () => products.reduce((sum, product) => sum + Number(product.price) * Number(product.quantity), 0),
    [products],
  )
  const totalUnits = useMemo(
    () => products.reduce((sum, product) => sum + Number(product.quantity), 0),
    [products],
  )

  async function signIn(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    try {
      const payload = await request('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify(credentials),
      })
      const productPayload = await request('/api/products', { token: payload.tokens.access_token })
      setProducts(productPayload.products)
      saveTokens(payload.tokens)
      setUser(payload.user)
      setCredentials({ username: '', password: '' })
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
      setLoading(false)
    }
  }

  async function signOut() {
    setBusy(true)
    setError('')
    try {
      await request('/api/auth/logout', {
        method: 'POST',
        token: tokens?.access_token,
        body: JSON.stringify({ refresh_token: tokens?.refresh_token }),
      })
      setNotice('You have been signed out.')
    } catch (err) {
      setError(err.message)
    } finally {
      sessionStorage.removeItem(TOKEN_KEY)
      setTokens(null)
      setUser(null)
      setProducts([])
      setBusy(false)
    }
  }

  function openCreate() {
    setForm({ product_name: '', description: '', price: '', quantity: '' })
    setModal({ type: 'create' })
    setError('')
  }

  function openEdit(product) {
    setForm({
      product_name: product.product_name,
      description: product.description,
      price: product.price,
      quantity: String(product.quantity),
    })
    setModal({ type: 'edit', product })
    setError('')
  }

  async function saveProduct(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    try {
      const editing = modal.type === 'edit'
      const payload = {
        product_name: form.product_name,
        description: form.description,
        price: form.price,
        quantity: Number(form.quantity),
      }
      await request(editing ? `/api/products/${modal.product.id}` : '/api/products', {
        method: editing ? 'PUT' : 'POST',
        token: tokens.access_token,
        body: JSON.stringify(payload),
      })
      await loadProducts(tokens.access_token)
      setModal(null)
      setNotice(editing ? 'Product details saved.' : 'Product added to your inventory.')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function deleteProduct() {
    setBusy(true)
    setError('')
    try {
      await request(`/api/products/${modal.product.id}`, {
        method: 'DELETE',
        token: tokens.access_token,
      })
      await loadProducts(tokens.access_token)
      setModal(null)
      setNotice('Product removed from your inventory.')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  if (loading) {
    return <main className="boot-screen"><span className="loader" />Connecting to Stockroom</main>
  }

  if (!user) {
    return (
      <main className="login-page">
        <section className="login-card">
          <div className="brand-mark">S</div>
          <p className="eyebrow">STOCKROOM · INVENTORY</p>
          <h1>Good to see you.</h1>
          <p className="muted">Sign in to manage your product catalog.</p>
          {error && <div className="alert error" role="alert">{error}</div>}
          {notice && <div className="alert success" role="status">{notice}</div>}
          <form className="login-form" onSubmit={signIn}>
            <label htmlFor="username">Username</label>
            <input
              id="username"
              autoComplete="username"
              value={credentials.username}
              onChange={(event) => setCredentials({ ...credentials, username: event.target.value })}
              required
            />
            <label htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              value={credentials.password}
              onChange={(event) => setCredentials({ ...credentials, password: event.target.value })}
              required
            />
            <button className="primary-button full-button" disabled={busy}>
              {busy ? 'Signing in…' : 'Sign in'}
              <span aria-hidden="true">↗</span>
            </button>
          </form>
          <p className="login-foot">Protected with LavaLust API authentication</p>
        </section>
        <div className="login-side">
          <div className="side-note"><span className="live-dot" /> YOUR INVENTORY, IN FOCUS</div>
          <div>
            <p className="side-kicker">PRODUCT MANAGEMENT</p>
            <h2>Make room<br />for what’s next.</h2>
            <p>One simple place to keep your catalog moving.</p>
          </div>
          <div className="side-index"><span>01</span><span className="index-line" /><span>STOCKROOM</span></div>
        </div>
      </main>
    )
  }

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <a className="brand" href="#inventory" aria-label="Stockroom home">
          <span className="brand-mark small-mark">S</span><span>stockroom<span className="brand-period">.</span></span>
        </a>
        <div className="nav-label">WORKSPACE</div>
        <a className="nav-link active" href="#inventory"><span className="nav-icon">▦</span>Inventory</a>
        <div className="sidebar-bottom">
          <div className="connection"><span className="live-dot" /><span><strong>Database connected</strong><small>Aiven MySQL · secure</small></span></div>
          <div className="profile">
            <div className="avatar">{(user.username || 'A').slice(0, 1).toUpperCase()}</div>
            <div className="profile-name"><strong>{user.username}</strong><small>Administrator</small></div>
            <button className="icon-button signout-icon" onClick={signOut} disabled={busy} aria-label="Sign out" title="Sign out">↗</button>
          </div>
        </div>
      </aside>

      <main className="main-content" id="inventory">
        <header className="topbar">
          <div className="breadcrumb">Workspace <span>/</span> <strong>Inventory</strong></div>
          <div className="topbar-right"><span className="today">PRODUCT MANAGEMENT SYSTEM</span><span className="top-avatar">{(user.username || 'A').slice(0, 1).toUpperCase()}</span></div>
        </header>

        <section className="page-content">
          <div className="page-heading">
            <div>
              <p className="eyebrow">CATALOG OVERVIEW <span className="heading-rule" /></p>
              <h1>Your inventory<span className="heading-period">.</span></h1>
              <p className="muted">A clear view of your products and available stock.</p>
            </div>
            <button className="primary-button" onClick={openCreate}><span className="plus">+</span> Add product</button>
          </div>

          {error && <div className="alert error" role="alert">{error}<button className="alert-close" onClick={() => setError('')} aria-label="Dismiss error">×</button></div>}
          {notice && <div className="alert success" role="status">{notice}<button className="alert-close" onClick={() => setNotice('')} aria-label="Dismiss message">×</button></div>}

          <div className="metrics-grid">
            <article className="metric-card"><div className="metric-top"><span>TOTAL PRODUCTS</span><span className="metric-glyph">▦</span></div><strong>{products.length.toString().padStart(2, '0')}</strong><small>items in your catalog</small></article>
            <article className="metric-card"><div className="metric-top"><span>UNITS IN STOCK</span><span className="metric-glyph">↗</span></div><strong>{totalUnits.toLocaleString()}</strong><small>available across products</small></article>
            <article className="metric-card value-card"><div className="metric-top"><span>INVENTORY VALUE</span><span className="metric-glyph">$</span></div><strong>${inventoryValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong><small>based on current quantity</small></article>
          </div>

          <section className="inventory-panel">
            <div className="panel-heading"><div><h2>All products</h2><p>Keep your catalog up to date.</p></div><span className="product-count">{products.length} {products.length === 1 ? 'PRODUCT' : 'PRODUCTS'}</span></div>
            {products.length === 0 ? (
              <div className="empty-state"><div className="empty-icon">▦</div><h3>Your inventory is ready.</h3><p>Add your first product to start tracking stock and value.</p><button className="secondary-button" onClick={openCreate}><span className="plus">+</span> Add your first product</button></div>
            ) : (
              <div className="table-wrap">
                <table>
                  <thead><tr><th>PRODUCT</th><th>DESCRIPTION</th><th>PRICE</th><th>QUANTITY</th><th>CREATED</th><th><span className="sr-only">Actions</span></th></tr></thead>
                  <tbody>{products.map((product) => (
                    <tr key={product.id}>
                      <td><div className="product-cell"><span className="product-initial">{product.product_name.slice(0, 1).toUpperCase()}</span><strong>{product.product_name}</strong></div></td>
                      <td className="description-cell">{product.description || <span className="muted">No description</span>}</td>
                      <td className="price-cell">${Number(product.price).toFixed(2)}</td>
                      <td><span className={`stock-pill ${Number(product.quantity) === 0 ? 'out-of-stock' : ''}`}><span />{Number(product.quantity)} units</span></td>
                      <td className="date-cell">{new Date(`${product.created_at.replace(' ', 'T')}Z`).toLocaleDateString()}</td>
                      <td><div className="row-actions"><button onClick={() => openEdit(product)} className="text-action">Edit</button><button onClick={() => { setModal({ type: 'delete', product }); setError('') }} className="text-action danger-action">Delete</button></div></td>
                    </tr>
                  ))}</tbody>
                </table>
              </div>
            )}
          </section>
          <footer className="page-footer"><span>STOCKROOM INVENTORY</span><span>CONNECTED TO LAVALUST API <i className="live-dot" /></span></footer>
        </section>
      </main>

      {modal && (
        <div className="modal-backdrop" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget && !busy) setModal(null) }}>
          <section className="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div className="modal-header">
              <div><p className="eyebrow">{modal.type === 'create' ? 'NEW CATALOG ITEM' : modal.type === 'edit' ? 'UPDATE CATALOG ITEM' : 'CONFIRM ACTION'}</p><h2 id="modal-title">{modal.type === 'create' ? 'Add a product' : modal.type === 'edit' ? 'Edit product' : 'Remove product?'}</h2></div>
              <button className="icon-button modal-close" onClick={() => setModal(null)} disabled={busy} aria-label="Close dialog">×</button>
            </div>
            {modal.type === 'delete' ? (
              <div className="delete-copy"><p>This will permanently remove <strong>{modal.product.product_name}</strong> from your inventory.</p><div className="modal-actions"><button className="secondary-button" onClick={() => setModal(null)} disabled={busy}>Keep product</button><button className="delete-button" onClick={deleteProduct} disabled={busy}>{busy ? 'Removing…' : 'Yes, remove product'}</button></div></div>
            ) : (
              <form className="product-form" onSubmit={saveProduct}>
                <label htmlFor="product-name">Product name</label><input id="product-name" maxLength="100" value={form.product_name} onChange={(event) => setForm({ ...form, product_name: event.target.value })} required />
                <label htmlFor="product-description">Description</label><textarea id="product-description" rows="3" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} required />
                <div className="form-row"><div><label htmlFor="product-price">Price</label><div className="input-prefix"><span>$</span><input id="product-price" type="number" min="0" max="99999999.99" step="0.01" value={form.price} onChange={(event) => setForm({ ...form, price: event.target.value })} required /></div></div><div><label htmlFor="product-quantity">Quantity</label><input id="product-quantity" type="number" min="0" max="2147483647" step="1" value={form.quantity} onChange={(event) => setForm({ ...form, quantity: event.target.value })} required /></div></div>
                <div className="modal-actions"><button type="button" className="secondary-button" onClick={() => setModal(null)} disabled={busy}>Cancel</button><button className="primary-button" disabled={busy}>{busy ? 'Saving…' : modal.type === 'create' ? 'Save product' : 'Save changes'}<span aria-hidden="true">↗</span></button></div>
              </form>
            )}
          </section>
        </div>
      )}
    </div>
  )
}

export default App
