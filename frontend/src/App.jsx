import { useEffect, useState } from 'react'
import './App.css'

const API_URL = (import.meta.env.VITE_API_URL || window.location.origin).replace(/\/+$/, '')
const ACCESS_TOKEN_KEY = 'stockroom-access-token'
const REFRESH_TOKEN_KEY = 'stockroom-refresh-token'
const USER_KEY = 'stockroom-user'
const IS_PRODUCT_MANAGER = import.meta.env.VITE_PRODUCT_MANAGER_REDESIGN === 'true'
let pendingTokenRefresh = null

function clearSession() {
  localStorage.removeItem(ACCESS_TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
}

function storeTokens(tokens) {
  if (!tokens?.access_token || !tokens?.refresh_token) {
    throw new Error('The API did not return both authentication tokens.')
  }
  localStorage.setItem(ACCESS_TOKEN_KEY, tokens.access_token)
  localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refresh_token)
}

function responseError(payload, status) {
  const payloadError = payload?.error
  const message = typeof payloadError === 'string'
    ? payloadError
    : typeof payloadError?.message === 'string'
      ? payloadError.message
      : typeof payload?.message === 'string'
        ? payload.message
        : `Request failed with status ${status}.`
  const error = new Error(message)
  error.status = status
  return error
}

async function sendRequest(path, { method = 'GET', body, token } = {}) {
  if (!API_URL) {
    throw new Error('The API URL is not configured. Set VITE_API_URL and rebuild the frontend.')
  }

  const response = await fetch(`${API_URL}${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    ...(body === undefined ? {} : { body: JSON.stringify(body) }),
  })
  const payload = response.status === 204 ? null : await response.json().catch(() => null)
  return { response, payload }
}

function refreshTokens(refreshToken) {
  if (!pendingTokenRefresh) {
    pendingTokenRefresh = (async () => {
      const result = await sendRequest('/api/auth/refresh', {
        method: 'POST',
        body: { refresh_token: refreshToken },
      })
      if (!result.response.ok) {
        throw responseError(result.payload, result.response.status)
      }
      storeTokens(result.payload?.tokens)
      return result.payload.tokens
    })().finally(() => {
      pendingTokenRefresh = null
    })
  }
  return pendingTokenRefresh
}

async function apiRequest(path, options = {}) {
  let result = await sendRequest(path, {
    ...options,
    token: localStorage.getItem(ACCESS_TOKEN_KEY),
  })

  if (result.response.status === 401) {
    const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY)
    if (!refreshToken) {
      clearSession()
      const error = new Error('Your session has expired. Please log in again.')
      error.authExpired = true
      throw error
    }

    try {
      const tokens = await refreshTokens(refreshToken)
      result = await sendRequest(path, { ...options, token: tokens.access_token })
    } catch {
      clearSession()
      const error = new Error('Your session has expired. Please log in again.')
      error.authExpired = true
      throw error
    }

    if (result.response.status === 401) {
      clearSession()
      const error = new Error('Your session has expired. Please log in again.')
      error.authExpired = true
      throw error
    }
  }

  if (!result.response.ok) {
    throw responseError(result.payload, result.response.status)
  }
  return result.payload
}

function App() {
  const [credentials, setCredentials] = useState({ username: '', password: '' })
  const [registerForm, setRegisterForm] = useState({ username: '', email: '', password: '' })
  const [authView, setAuthView] = useState('login')
  const [user, setUser] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem(USER_KEY) || 'null')
    } catch {
      localStorage.removeItem(USER_KEY)
      return null
    }
  })
  const [products, setProducts] = useState([])
  const [activeView, setActiveView] = useState('products')
  const [productForm, setProductForm] = useState({
    product_name: '',
    description: '',
    price: '',
    quantity: '',
  })
  const [editingProduct, setEditingProduct] = useState(null)
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [loggingOut, setLoggingOut] = useState(false)
  const [productsLoading, setProductsLoading] = useState(false)
  const [message, setMessage] = useState('')
  const [messageType, setMessageType] = useState('success')

  function announce(text, type = 'success') {
    setMessage(text)
    setMessageType(type)
  }

  function expireSession(error) {
    if (!error.authExpired) return false
    clearSession()
    setUser(null)
    setProducts([])
    announce(error.message, 'error')
    return true
  }

  function showError(error) {
    if (expireSession(error)) return
    announce(
      error instanceof TypeError
        ? 'Could not connect to the API. Check your connection and try again.'
        : error.message,
      'error',
    )
  }

  async function loadProducts(successMessage = '') {
    setProductsLoading(true)
    try {
      const payload = await apiRequest('/api/products')
      if (!Array.isArray(payload?.products)) {
        throw new Error('The API returned an unexpected product list.')
      }
      setProducts(payload.products)
      if (successMessage) announce(successMessage)
      return true
    } catch (error) {
      showError(error)
      return false
    } finally {
      setProductsLoading(false)
    }
  }

  useEffect(() => {
    let active = true

    async function restoreSession() {
      const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY)
      if (!refreshToken) {
        if (active) setLoading(false)
        return
      }

      try {
        await refreshTokens(refreshToken)
        const [profile, list] = await Promise.all([
          apiRequest('/api/auth/me'),
          apiRequest('/api/products'),
        ])
        if (!Array.isArray(list?.products)) {
          throw new Error('The API returned an unexpected product list.')
        }
        if (active) {
          setUser(profile?.user || null)
          setProducts(list.products)
        }
      } catch (error) {
        clearSession()
        if (active) {
          setUser(null)
          setProducts([])
          if (error instanceof TypeError || !error.authExpired) showError(error)
          else announce('Your session has expired. Please log in again.', 'error')
        }
      } finally {
        if (active) setLoading(false)
      }
    }

    restoreSession()
    return () => { active = false }
  }, [])

  async function handleLogin(event) {
    event.preventDefault()
    setBusy(true)
    setMessage('')
    try {
      const payload = await apiRequest('/api/auth/login', {
        method: 'POST',
        body: credentials,
      })
      storeTokens(payload?.tokens)
      localStorage.setItem(USER_KEY, JSON.stringify(payload.user))
      setUser(payload.user)
      setCredentials({ username: '', password: '' })
      setActiveView('products')
      await loadProducts('Login successful. Products loaded successfully!')
    } catch (error) {
      showError(error)
    } finally {
      setBusy(false)
      setLoading(false)
    }
  }

  async function handleRegister(event) {
    event.preventDefault()
    setBusy(true)
    setMessage('')
    try {
      const payload = await apiRequest('/api/auth/register', {
        method: 'POST',
        body: registerForm,
      })
      setCredentials({ username: registerForm.username.trim(), password: '' })
      setRegisterForm({ username: '', email: '', password: '' })
      setAuthView('login')
      announce(payload?.message || 'Account created successfully. You can now log in.')
    } catch (error) {
      showError(error)
    } finally {
      setBusy(false)
    }
  }

  async function handleLogout() {
    const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY)
    setBusy(true)
    setLoggingOut(true)
    let logoutError = ''
    try {
      if (refreshToken) {
        const tokens = await refreshTokens(refreshToken)
        await apiRequest('/api/auth/logout', {
          method: 'POST',
          body: { refresh_token: tokens.refresh_token },
        })
      }
    } catch (error) {
      logoutError = error instanceof TypeError
        ? 'The API could not be reached to revoke the session.'
        : error.message
    } finally {
      clearSession()
      setUser(null)
      setProducts([])
      setActiveView('products')
      setEditingProduct(null)
      setAuthView('login')
      setBusy(false)
      setLoggingOut(false)
      announce(
        logoutError
          ? `Signed out on this device, but the server could not revoke the session: ${logoutError}`
          : 'You have been logged out.',
        logoutError ? 'error' : 'success',
      )
    }
  }

  async function handleAddProduct(event) {
    event.preventDefault()
    setBusy(true)
    setMessage('')
    try {
      await apiRequest('/api/products', { method: 'POST', body: productForm })
      setProductForm({ product_name: '', description: '', price: '', quantity: '' })
      setActiveView('products')
      await loadProducts('Product added successfully!')
    } catch (error) {
      showError(error)
    } finally {
      setBusy(false)
    }
  }

  function startEdit(product) {
    setEditingProduct({
      id: product.id,
      product_name: product.product_name || '',
      description: product.description || '',
      price: String(product.price ?? ''),
      quantity: String(product.quantity ?? ''),
    })
    setMessage('')
  }

  async function handleSaveProduct(event) {
    event.preventDefault()
    if (!editingProduct) return
    setBusy(true)
    setMessage('')
    try {
      await apiRequest(`/api/products/${editingProduct.id}`, {
        method: 'PUT',
        body: {
          product_name: editingProduct.product_name,
          description: editingProduct.description,
          price: editingProduct.price,
          quantity: editingProduct.quantity,
        },
      })
      setEditingProduct(null)
      await loadProducts('Product updated successfully!')
    } catch (error) {
      showError(error)
    } finally {
      setBusy(false)
    }
  }

  async function handleDeleteProduct(id) {
    if (!window.confirm('Are you sure you want to delete this product?')) return
    setBusy(true)
    setMessage('')
    try {
      await apiRequest(`/api/products/${id}`, { method: 'DELETE' })
      await loadProducts('Product deleted successfully!')
    } catch (error) {
      showError(error)
    } finally {
      setBusy(false)
    }
  }

  const inventorySummary = products.reduce((summary, product) => {
    const quantity = Number(product.quantity) || 0
    summary.units += quantity
    summary.value += (Number(product.price) || 0) * quantity
    return summary
  }, { units: 0, value: 0 })

  if (loading) {
    return <main className={`loading-screen${IS_PRODUCT_MANAGER ? ' manager-redesign' : ''}`}><span className="loader" />Connecting to Product Management</main>
  }

  if (!user) {
    return (
      <main className={`auth-page${IS_PRODUCT_MANAGER ? ' manager-redesign' : ''}`}>
        {IS_PRODUCT_MANAGER && (
          <aside className="manager-auth-story">
            <div className="manager-brand"><span className="manager-brand-mark">S</span> STOCKROOM</div>
            <div>
              <p className="manager-overline">INVENTORY, IN FOCUS</p>
              <h2>A better view of everything you stock.</h2>
              <p>One workspace to keep your catalog, pricing, and stock moving in the right direction.</p>
            </div>
            <span className="manager-story-foot">PRODUCT OPERATIONS · EST. 2026</span>
          </aside>
        )}
        <section className="auth-card">
          <p className="eyebrow">{IS_PRODUCT_MANAGER ? 'STOCKROOM WORKSPACE' : 'PRODUCT SYSTEM'}</p>
          <h1>{authView === 'login' ? 'Product Management' : 'Create an account'}</h1>
          <p className="auth-subtitle">
            {authView === 'login' ? 'Login to continue to your dashboard.' : 'Register to manage your product inventory.'}
          </p>
          {message && <div className={`status-message ${messageType}`} role={messageType === 'error' ? 'alert' : 'status'}>{message}</div>}

          {authView === 'login' ? (
            <form className="auth-form" onSubmit={handleLogin}>
              <label htmlFor="login-username">Username or email</label>
              <input id="login-username" autoComplete="username" value={credentials.username} onChange={(event) => setCredentials({ ...credentials, username: event.target.value })} required />
              <label htmlFor="login-password">Password</label>
              <input id="login-password" type="password" autoComplete="current-password" value={credentials.password} onChange={(event) => setCredentials({ ...credentials, password: event.target.value })} required />
              <button className="primary-button" type="submit" disabled={busy}>{busy ? 'Logging in…' : 'Login'}</button>
            </form>
          ) : (
            <form className="auth-form" onSubmit={handleRegister}>
              <label htmlFor="register-username">Username</label>
              <input id="register-username" autoComplete="username" maxLength="100" value={registerForm.username} onChange={(event) => setRegisterForm({ ...registerForm, username: event.target.value })} required />
              <label htmlFor="register-email">Email</label>
              <input id="register-email" type="email" autoComplete="email" maxLength="255" value={registerForm.email} onChange={(event) => setRegisterForm({ ...registerForm, email: event.target.value })} required />
              <label htmlFor="register-password">Password</label>
              <input id="register-password" type="password" autoComplete="new-password" minLength="8" maxLength="72" value={registerForm.password} onChange={(event) => setRegisterForm({ ...registerForm, password: event.target.value })} required />
              <button className="primary-button" type="submit" disabled={busy}>{busy ? 'Creating account…' : 'Create an account'}</button>
            </form>
          )}

          <p className="auth-switch">
            {authView === 'login' ? 'New here?' : 'Already have an account?'}{' '}
            <button type="button" onClick={() => { setAuthView(authView === 'login' ? 'register' : 'login'); setMessage('') }}>
              {authView === 'login' ? 'Create an account' : 'Back to Login'}
            </button>
          </p>
        </section>
      </main>
    )
  }

  return (
    <main className={`dashboard-page${IS_PRODUCT_MANAGER ? ' manager-redesign' : ''}`}>
      {IS_PRODUCT_MANAGER && (
        <aside className="manager-sidebar">
          <a className="manager-brand" href="/" aria-label="Stockroom home"><span className="manager-brand-mark">S</span> STOCKROOM</a>
          <p className="manager-nav-label">WORKSPACE</p>
          <nav className="manager-nav" aria-label="Dashboard navigation">
            <button type="button" className={activeView === 'products' ? 'active' : ''} onClick={() => { setActiveView('products'); setEditingProduct(null); setMessage('') }}>
              <span aria-hidden="true">▦</span> Inventory
            </button>
            <button type="button" className={activeView === 'add' ? 'active' : ''} onClick={() => { setActiveView('add'); setEditingProduct(null); setMessage('') }}>
              <span aria-hidden="true">＋</span> Add product
            </button>
          </nav>
          <div className="manager-sidebar-bottom">
            <div className="manager-sidebar-user"><span className="manager-avatar">{user.username?.charAt(0)?.toUpperCase() || 'U'}</span><span><strong>{user.username}</strong><small>Workspace account</small></span></div>
            <button className="manager-sidebar-logout" type="button" onClick={handleLogout} disabled={busy}>{loggingOut ? 'Logging out…' : 'Sign out'}</button>
          </div>
        </aside>
      )}
      <section className="dashboard-card">
        {IS_PRODUCT_MANAGER && (
          <div className="manager-toolbar">
            <span>Workspace <span aria-hidden="true">/</span> {activeView === 'products' ? 'Inventory' : 'New product'}</span>
            <span className="manager-live"><i aria-hidden="true" /> Inventory is up to date</span>
          </div>
        )}
        <header className="dashboard-header">
          <div>
            <p className="eyebrow">{IS_PRODUCT_MANAGER ? 'YOUR BUSINESS AT A GLANCE' : 'PRODUCT SYSTEM'}</p>
            <h1>{IS_PRODUCT_MANAGER ? (activeView === 'products' ? 'Inventory' : 'Add a product') : 'Product Management'}</h1>
            <p className="welcome">Welcome, {user.username}</p>
          </div>
          {!IS_PRODUCT_MANAGER && <button className="logout-button" type="button" onClick={handleLogout} disabled={busy}>{loggingOut ? 'Logging out…' : 'Logout'}</button>}
        </header>

        {IS_PRODUCT_MANAGER && activeView === 'products' && (
          <section className="manager-metrics" aria-label="Inventory summary">
            <article><span className="manager-metric-icon">▦</span><span><small>CATALOG ITEMS</small><strong>{products.length.toLocaleString()}</strong></span></article>
            <article><span className="manager-metric-icon">↗</span><span><small>UNITS IN STOCK</small><strong>{inventorySummary.units.toLocaleString()}</strong></span></article>
            <article><span className="manager-metric-icon">$</span><span><small>INVENTORY VALUE</small><strong>{new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(inventorySummary.value)}</strong></span></article>
          </section>
        )}

        <nav className={`product-tabs${IS_PRODUCT_MANAGER ? ' manager-product-tabs' : ''}`} aria-label="Product management">
          <button type="button" className={activeView === 'products' ? 'active' : ''} onClick={() => { setActiveView('products'); setMessage('') }}>View Products</button>
          <button type="button" className={activeView === 'add' ? 'active' : ''} onClick={() => { setActiveView('add'); setMessage('') }}>Add Product</button>
        </nav>

        {message && <div className={`status-message ${messageType}`} role={messageType === 'error' ? 'alert' : 'status'}>{message}</div>}

        {activeView === 'products' ? (
          <section className="content-section">
            <div className="section-heading">
              <div>
                <p className="section-label">YOUR INVENTORY</p>
                <h2>View Products</h2>
              </div>
              <button className="primary-button load-button" type="button" onClick={() => loadProducts('Products loaded successfully!')} disabled={productsLoading}>
                {productsLoading ? 'Loading products…' : 'Load Products'}
              </button>
            </div>

            {productsLoading && products.length === 0 && <p className="empty-state">Loading products…</p>}
            {!productsLoading && products.length === 0 && <p className="empty-state">No products yet. Add a product to get started.</p>}
            <div className="products-grid">
              {products.map((product) => (
                <article className="product-card" key={product.id}>
                  {editingProduct?.id === product.id ? (
                    <form className="edit-form" onSubmit={handleSaveProduct}>
                      <p className="section-label">PRODUCT #{product.id}</p>
                      <h3>Update Product</h3>
                      <label htmlFor={`edit-name-${product.id}`}>Product Name</label>
                      <input id={`edit-name-${product.id}`} maxLength="100" value={editingProduct.product_name} onChange={(event) => setEditingProduct({ ...editingProduct, product_name: event.target.value })} required />
                      <label htmlFor={`edit-description-${product.id}`}>Description</label>
                      <textarea id={`edit-description-${product.id}`} rows="3" value={editingProduct.description} onChange={(event) => setEditingProduct({ ...editingProduct, description: event.target.value })} required />
                      <div className="form-row">
                        <div><label htmlFor={`edit-price-${product.id}`}>Price</label><input id={`edit-price-${product.id}`} type="number" min="0" max="99999999.99" step="0.01" value={editingProduct.price} onChange={(event) => setEditingProduct({ ...editingProduct, price: event.target.value })} required /></div>
                        <div><label htmlFor={`edit-quantity-${product.id}`}>Quantity</label><input id={`edit-quantity-${product.id}`} type="number" min="0" max="2147483647" step="1" value={editingProduct.quantity} onChange={(event) => setEditingProduct({ ...editingProduct, quantity: event.target.value })} required /></div>
                      </div>
                      <div className="edit-actions">
                        <button className="primary-button" type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save Changes'}</button>
                        <button className="secondary-button" type="button" onClick={() => setEditingProduct(null)} disabled={busy}>Cancel</button>
                      </div>
                    </form>
                  ) : (
                    <>
                      <p className="product-number">PRODUCT #{product.id}</p>
                      <h3>{product.product_name}</h3>
                      <p className="product-description">{product.description || 'No description provided.'}</p>
                      <div className="product-details">
                        <strong>{new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(product.price))}</strong>
                        <span>Quantity: {Number(product.quantity)}</span>
                      </div>
                      <div className="product-actions">
                        <button className="secondary-button" type="button" onClick={() => startEdit(product)}>Edit</button>
                        <button className="delete-button" type="button" onClick={() => handleDeleteProduct(product.id)} disabled={busy}>Delete</button>
                      </div>
                    </>
                  )}
                </article>
              ))}
            </div>
          </section>
        ) : (
          <section className="content-section">
            <div className="section-heading">
              <div>
                <p className="section-label">NEW ITEM</p>
                <h2>Add Product</h2>
              </div>
            </div>
            <form className="product-form" onSubmit={handleAddProduct}>
              <label htmlFor="product-name">Product Name</label>
              <input id="product-name" maxLength="100" value={productForm.product_name} onChange={(event) => setProductForm({ ...productForm, product_name: event.target.value })} required />
              <label htmlFor="product-description">Description</label>
              <textarea id="product-description" rows="3" value={productForm.description} onChange={(event) => setProductForm({ ...productForm, description: event.target.value })} required />
              <div className="form-row">
                <div><label htmlFor="product-price">Price</label><input id="product-price" type="number" min="0" max="99999999.99" step="0.01" value={productForm.price} onChange={(event) => setProductForm({ ...productForm, price: event.target.value })} required /></div>
                <div><label htmlFor="product-quantity">Quantity</label><input id="product-quantity" type="number" min="0" max="2147483647" step="1" value={productForm.quantity} onChange={(event) => setProductForm({ ...productForm, quantity: event.target.value })} required /></div>
              </div>
              <button className="primary-button" type="submit" disabled={busy}>{busy ? 'Adding product…' : 'Add Product'}</button>
            </form>
          </section>
        )}
      </section>
    </main>
  )
}

export default App
