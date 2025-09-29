import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Layout from './components/Layout/Layout';
import Dashboard from './pages/Dashboard';
import Clients from './pages/Clients';
import Billing from './pages/Billing';
import Services from './pages/Services';
import Domains from './pages/Domains';
import Support from './pages/Support';
import { Toaster } from './components/ui/toaster';
import './App.css';

// Mock pages for routes that don't have dedicated components yet
const Reports = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Reports</h1>
    <p className="text-gray-600">Financial and operational reports will be displayed here.</p>
  </div>
);

const Calendar = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Calendar</h1>
    <p className="text-gray-600">Admin calendar and scheduling features will be displayed here.</p>
  </div>
);

const Settings = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Settings</h1>
    <p className="text-gray-600">System configuration and settings will be displayed here.</p>
  </div>
);

const BillingInvoices = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Invoices</h1>
    <p className="text-gray-600">Detailed invoice management will be displayed here.</p>
  </div>
);

const BillingPayments = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Payments</h1>
    <p className="text-gray-600">Payment processing and history will be displayed here.</p>
  </div>
);

const BillingTransactions = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Transactions</h1>
    <p className="text-gray-600">Transaction history and details will be displayed here.</p>
  </div>
);

const SupportTickets = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Support Tickets</h1>
    <p className="text-gray-600">Detailed ticket management will be displayed here.</p>
  </div>
);

const SupportKB = () => (
  <div className="p-6">
    <h1 className="text-3xl font-bold text-gray-900 mb-4">Knowledge Base</h1>
    <p className="text-gray-600">Knowledge base articles and documentation will be displayed here.</p>
  </div>
);

function App() {
  return (
    <div className="App">
      <BrowserRouter>
        <Layout>
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/clients" element={<Clients />} />
            <Route path="/billing" element={<Billing />} />
            <Route path="/billing/invoices" element={<BillingInvoices />} />
            <Route path="/billing/payments" element={<BillingPayments />} />
            <Route path="/billing/transactions" element={<BillingTransactions />} />
            <Route path="/services" element={<Services />} />
            <Route path="/domains" element={<Domains />} />
            <Route path="/support" element={<Support />} />
            <Route path="/support/tickets" element={<SupportTickets />} />
            <Route path="/support/kb" element={<SupportKB />} />
            <Route path="/reports" element={<Reports />} />
            <Route path="/calendar" element={<Calendar />} />
            <Route path="/settings" element={<Settings />} />
          </Routes>
        </Layout>
        <Toaster />
      </BrowserRouter>
    </div>
  );
}

export default App;