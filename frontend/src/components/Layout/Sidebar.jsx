import React from 'react';
import { NavLink } from 'react-router-dom';
import { cn } from '../../lib/utils';
import {
  LayoutDashboard,
  Users,
  FileText,
  CreditCard,
  Globe,
  HelpCircle,
  Settings,
  BarChart3,
  Calendar,
  Bell,
  Package
} from 'lucide-react';

const navigationItems = [
  {
    title: 'Dashboard',
    href: '/',
    icon: LayoutDashboard
  },
  {
    title: 'Clients',
    href: '/clients',
    icon: Users
  },
  {
    title: 'Billing',
    href: '/billing',
    icon: CreditCard,
    children: [
      { title: 'Invoices', href: '/billing/invoices' },
      { title: 'Payments', href: '/billing/payments' },
      { title: 'Transactions', href: '/billing/transactions' }
    ]
  },
  {
    title: 'Services',
    href: '/services',
    icon: Package
  },
  {
    title: 'Domains',
    href: '/domains',
    icon: Globe
  },
  {
    title: 'Support',
    href: '/support',
    icon: HelpCircle,
    children: [
      { title: 'Tickets', href: '/support/tickets' },
      { title: 'Knowledge Base', href: '/support/kb' }
    ]
  },
  {
    title: 'Reports',
    href: '/reports',
    icon: BarChart3
  },
  {
    title: 'Calendar',
    href: '/calendar',
    icon: Calendar
  },
  {
    title: 'Settings',
    href: '/settings',
    icon: Settings
  }
];

const Sidebar = ({ isCollapsed }) => {
  return (
    <div className={cn(
      "bg-white border-r border-gray-200 transition-all duration-300 flex flex-col",
      isCollapsed ? "w-16" : "w-64"
    )}>
      {/* Logo */}
      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center space-x-3">
          <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <span className="text-white font-bold text-sm">W</span>
          </div>
          {!isCollapsed && (
            <span className="font-bold text-lg text-gray-900">WHMCS Admin</span>
          )}
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 py-4">
        <ul className="space-y-1 px-3">
          {navigationItems.map((item) => (
            <li key={item.href}>
              <NavLink
                to={item.href}
                className={({ isActive }) =>
                  cn(
                    "flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors",
                    isActive
                      ? "bg-blue-50 text-blue-700 border-r-2 border-blue-600"
                      : "text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                  )
                }
              >
                <item.icon className="w-5 h-5 flex-shrink-0" />
                {!isCollapsed && <span>{item.title}</span>}
              </NavLink>
              {item.children && !isCollapsed && (
                <ul className="ml-8 mt-1 space-y-1">
                  {item.children.map((child) => (
                    <li key={child.href}>
                      <NavLink
                        to={child.href}
                        className={({ isActive }) =>
                          cn(
                            "block px-3 py-1 text-sm rounded transition-colors",
                            isActive
                              ? "text-blue-700 bg-blue-50"
                              : "text-gray-500 hover:text-gray-700"
                          )
                        }
                      >
                        {child.title}
                      </NavLink>
                    </li>
                  ))}
                </ul>
              )}
            </li>
          ))}
        </ul>
      </nav>

      {/* Admin Info */}
      {!isCollapsed && (
        <div className="p-4 border-t border-gray-200">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
              <span className="text-gray-600 font-medium text-sm">A</span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">Admin User</p>
              <p className="text-xs text-gray-500">admin@company.com</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Sidebar;