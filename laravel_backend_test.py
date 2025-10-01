#!/usr/bin/env python3
"""
Laravel WHMCS Backend API Test Suite
Tests all Laravel backend endpoints with realistic data
"""

import requests
import json
import uuid
from datetime import datetime, timedelta
import sys
import os

# Get backend URL from environment
BACKEND_URL = "https://ticket-manager-20.preview.emergentagent.com"
API_BASE = f"{BACKEND_URL}/api"

class LaravelBackendTester:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })
        self.test_results = []
        self.created_client_id = None
        self.created_service_id = None
        self.created_domain_id = None
        self.created_invoice_id = None
        self.created_ticket_id = None
        
    def log_result(self, test_name, success, details, response_data=None):
        """Log test result"""
        result = {
            'test': test_name,
            'success': success,
            'details': details,
            'timestamp': datetime.now().isoformat()
        }
        if response_data:
            result['response_data'] = response_data
        self.test_results.append(result)
        
        status = "✅ PASS" if success else "❌ FAIL"
        print(f"{status} {test_name}: {details}")
        if response_data and not success:
            print(f"   Response: {json.dumps(response_data, indent=2)}")
    
    def test_dashboard_endpoint(self):
        """Test dashboard endpoint"""
        try:
            response = self.session.get(f"{API_BASE}/dashboard", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                expected_keys = ['stats', 'recent_clients', 'recent_invoices', 'recent_tickets']
                if all(key in data for key in expected_keys):
                    stats = data['stats']
                    stats_keys = ['total_clients', 'total_services', 'total_domains', 'total_invoices']
                    if any(key in stats for key in stats_keys):
                        self.log_result("Dashboard API", True, f"Dashboard data retrieved successfully", {
                            'total_clients': stats.get('total_clients'),
                            'total_services': stats.get('total_services'),
                            'total_invoices': stats.get('total_invoices'),
                            'recent_clients_count': len(data['recent_clients']),
                            'recent_invoices_count': len(data['recent_invoices'])
                        })
                        return True
                    else:
                        self.log_result("Dashboard API", False, f"Missing expected stats keys", data)
                        return False
                else:
                    self.log_result("Dashboard API", False, f"Missing expected response keys", data)
                    return False
            else:
                self.log_result("Dashboard API", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Dashboard API", False, f"Connection error: {str(e)}")
            return False
    
    def test_clients_list(self):
        """Test clients list endpoint with pagination"""
        try:
            response = self.session.get(f"{API_BASE}/clients", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                # Laravel pagination structure
                expected_keys = ['data', 'current_page', 'total', 'per_page', 'last_page']
                if all(key in data for key in expected_keys):
                    self.log_result("Clients List", True, f"Retrieved {data['total']} clients (page {data['current_page']})", {
                        'total': data['total'],
                        'current_page': data['current_page'],
                        'per_page': data['per_page'],
                        'last_page': data['last_page'],
                        'data_count': len(data['data'])
                    })
                    return True
                else:
                    self.log_result("Clients List", False, f"Missing expected pagination keys", data)
                    return False
            else:
                self.log_result("Clients List", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Clients List", False, f"Connection error: {str(e)}")
            return False
    
    def test_clients_search(self):
        """Test clients search functionality"""
        try:
            # Test search with a common term
            response = self.session.get(f"{API_BASE}/clients?search=test", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                self.log_result("Clients Search", True, f"Search executed successfully, found {data.get('total', 0)} results")
                return True
            else:
                self.log_result("Clients Search", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Clients Search", False, f"Connection error: {str(e)}")
            return False
    
    def test_clients_status_filter(self):
        """Test clients status filter"""
        try:
            response = self.session.get(f"{API_BASE}/clients?status=Active", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                self.log_result("Clients Status Filter", True, f"Status filter executed successfully, found {data.get('total', 0)} active clients")
                return True
            else:
                self.log_result("Clients Status Filter", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Clients Status Filter", False, f"Connection error: {str(e)}")
            return False
    
    def test_client_by_id(self):
        """Test getting client by ID"""
        try:
            # First get a client ID from the list
            response = self.session.get(f"{API_BASE}/clients?per_page=1", timeout=10)
            if response.status_code == 200:
                data = response.json()
                if data['data'] and len(data['data']) > 0:
                    client_id = data['data'][0]['id']
                    
                    # Now test getting this specific client
                    response = self.session.get(f"{API_BASE}/clients/{client_id}", timeout=10)
                    if response.status_code == 200:
                        client_data = response.json()
                        if client_data.get('id') == client_id:
                            self.log_result("Get Client by ID", True, f"Client {client_id} retrieved successfully with relationships", {
                                'id': client_data['id'],
                                'email': client_data.get('email'),
                                'first_name': client_data.get('first_name'),
                                'has_services': 'services' in client_data,
                                'has_domains': 'domains' in client_data,
                                'has_invoices': 'invoices' in client_data
                            })
                            return True
                        else:
                            self.log_result("Get Client by ID", False, f"ID mismatch in response", client_data)
                            return False
                    else:
                        self.log_result("Get Client by ID", False, f"HTTP {response.status_code}: {response.text}")
                        return False
                else:
                    self.log_result("Get Client by ID", False, "No clients found to test with")
                    return False
            else:
                self.log_result("Get Client by ID", False, f"Failed to get client list: HTTP {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Get Client by ID", False, f"Connection error: {str(e)}")
            return False
    
    def test_services_list(self):
        """Test services list endpoint"""
        try:
            response = self.session.get(f"{API_BASE}/services", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                expected_keys = ['data', 'current_page', 'total', 'per_page', 'last_page']
                if all(key in data for key in expected_keys):
                    self.log_result("Services List", True, f"Retrieved {data['total']} services (page {data['current_page']})", {
                        'total': data['total'],
                        'current_page': data['current_page'],
                        'data_count': len(data['data'])
                    })
                    return True
                else:
                    self.log_result("Services List", False, f"Missing expected pagination keys", data)
                    return False
            else:
                self.log_result("Services List", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Services List", False, f"Connection error: {str(e)}")
            return False
    
    def test_services_status_filter(self):
        """Test services status filter"""
        try:
            response = self.session.get(f"{API_BASE}/services?status=Active", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                self.log_result("Services Status Filter", True, f"Status filter executed successfully, found {data.get('total', 0)} active services")
                return True
            else:
                self.log_result("Services Status Filter", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Services Status Filter", False, f"Connection error: {str(e)}")
            return False
    
    def test_service_by_id(self):
        """Test getting service by ID"""
        try:
            # First get a service ID from the list
            response = self.session.get(f"{API_BASE}/services?per_page=1", timeout=10)
            if response.status_code == 200:
                data = response.json()
                if data['data'] and len(data['data']) > 0:
                    service_id = data['data'][0]['id']
                    
                    # Now test getting this specific service
                    response = self.session.get(f"{API_BASE}/services/{service_id}", timeout=10)
                    if response.status_code == 200:
                        service_data = response.json()
                        if service_data.get('id') == service_id:
                            self.log_result("Get Service by ID", True, f"Service {service_id} retrieved successfully", {
                                'id': service_data['id'],
                                'domain': service_data.get('domain'),
                                'status': service_data.get('status'),
                                'has_client': 'client' in service_data
                            })
                            return True
                        else:
                            self.log_result("Get Service by ID", False, f"ID mismatch in response", service_data)
                            return False
                    else:
                        self.log_result("Get Service by ID", False, f"HTTP {response.status_code}: {response.text}")
                        return False
                else:
                    self.log_result("Get Service by ID", False, "No services found to test with")
                    return False
            else:
                self.log_result("Get Service by ID", False, f"Failed to get service list: HTTP {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Get Service by ID", False, f"Connection error: {str(e)}")
            return False
    
    def test_domains_list(self):
        """Test domains list endpoint"""
        try:
            response = self.session.get(f"{API_BASE}/domains", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                expected_keys = ['data', 'current_page', 'total', 'per_page', 'last_page']
                if all(key in data for key in expected_keys):
                    self.log_result("Domains List", True, f"Retrieved {data['total']} domains (page {data['current_page']})", {
                        'total': data['total'],
                        'current_page': data['current_page'],
                        'data_count': len(data['data'])
                    })
                    return True
                else:
                    self.log_result("Domains List", False, f"Missing expected pagination keys", data)
                    return False
            else:
                self.log_result("Domains List", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Domains List", False, f"Connection error: {str(e)}")
            return False
    
    def test_domains_status_filter(self):
        """Test domains status filter"""
        try:
            response = self.session.get(f"{API_BASE}/domains?status=Active", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                self.log_result("Domains Status Filter", True, f"Status filter executed successfully, found {data.get('total', 0)} active domains")
                return True
            else:
                self.log_result("Domains Status Filter", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Domains Status Filter", False, f"Connection error: {str(e)}")
            return False
    
    def test_domain_by_id(self):
        """Test getting domain by ID"""
        try:
            # First get a domain ID from the list
            response = self.session.get(f"{API_BASE}/domains?per_page=1", timeout=10)
            if response.status_code == 200:
                data = response.json()
                if data['data'] and len(data['data']) > 0:
                    domain_id = data['data'][0]['id']
                    
                    # Now test getting this specific domain
                    response = self.session.get(f"{API_BASE}/domains/{domain_id}", timeout=10)
                    if response.status_code == 200:
                        domain_data = response.json()
                        if domain_data.get('id') == domain_id:
                            self.log_result("Get Domain by ID", True, f"Domain {domain_id} retrieved successfully", {
                                'id': domain_data['id'],
                                'domain': domain_data.get('domain'),
                                'status': domain_data.get('status')
                            })
                            return True
                        else:
                            self.log_result("Get Domain by ID", False, f"ID mismatch in response", domain_data)
                            return False
                    else:
                        self.log_result("Get Domain by ID", False, f"HTTP {response.status_code}: {response.text}")
                        return False
                else:
                    self.log_result("Get Domain by ID", False, "No domains found to test with")
                    return False
            else:
                self.log_result("Get Domain by ID", False, f"Failed to get domain list: HTTP {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Get Domain by ID", False, f"Connection error: {str(e)}")
            return False
    
    def test_invoices_list(self):
        """Test invoices list endpoint"""
        try:
            response = self.session.get(f"{API_BASE}/invoices", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                expected_keys = ['data', 'current_page', 'total', 'per_page', 'last_page']
                if all(key in data for key in expected_keys):
                    self.log_result("Invoices List", True, f"Retrieved {data['total']} invoices (page {data['current_page']})", {
                        'total': data['total'],
                        'current_page': data['current_page'],
                        'data_count': len(data['data'])
                    })
                    return True
                else:
                    self.log_result("Invoices List", False, f"Missing expected pagination keys", data)
                    return False
            else:
                self.log_result("Invoices List", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Invoices List", False, f"Connection error: {str(e)}")
            return False
    
    def test_invoices_status_filter(self):
        """Test invoices status filter"""
        try:
            response = self.session.get(f"{API_BASE}/invoices?status=Paid", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                self.log_result("Invoices Status Filter", True, f"Status filter executed successfully, found {data.get('total', 0)} paid invoices")
                return True
            else:
                self.log_result("Invoices Status Filter", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Invoices Status Filter", False, f"Connection error: {str(e)}")
            return False
    
    def test_invoice_by_id(self):
        """Test getting invoice by ID"""
        try:
            # First get an invoice ID from the list
            response = self.session.get(f"{API_BASE}/invoices?per_page=1", timeout=10)
            if response.status_code == 200:
                data = response.json()
                if data['data'] and len(data['data']) > 0:
                    invoice_id = data['data'][0]['id']
                    
                    # Now test getting this specific invoice
                    response = self.session.get(f"{API_BASE}/invoices/{invoice_id}", timeout=10)
                    if response.status_code == 200:
                        invoice_data = response.json()
                        if invoice_data.get('id') == invoice_id:
                            self.log_result("Get Invoice by ID", True, f"Invoice {invoice_id} retrieved successfully", {
                                'id': invoice_data['id'],
                                'amount': invoice_data.get('amount'),
                                'status': invoice_data.get('status'),
                                'has_client': 'client' in invoice_data
                            })
                            return True
                        else:
                            self.log_result("Get Invoice by ID", False, f"ID mismatch in response", invoice_data)
                            return False
                    else:
                        self.log_result("Get Invoice by ID", False, f"HTTP {response.status_code}: {response.text}")
                        return False
                else:
                    self.log_result("Get Invoice by ID", False, "No invoices found to test with")
                    return False
            else:
                self.log_result("Get Invoice by ID", False, f"Failed to get invoice list: HTTP {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Get Invoice by ID", False, f"Connection error: {str(e)}")
            return False
    
    def test_tickets_list(self):
        """Test tickets list endpoint"""
        try:
            response = self.session.get(f"{API_BASE}/tickets", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                expected_keys = ['data', 'current_page', 'total', 'per_page', 'last_page']
                if all(key in data for key in expected_keys):
                    self.log_result("Tickets List", True, f"Retrieved {data['total']} tickets (page {data['current_page']})", {
                        'total': data['total'],
                        'current_page': data['current_page'],
                        'data_count': len(data['data'])
                    })
                    return True
                else:
                    self.log_result("Tickets List", False, f"Missing expected pagination keys", data)
                    return False
            else:
                self.log_result("Tickets List", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Tickets List", False, f"Connection error: {str(e)}")
            return False
    
    def test_ticket_by_id(self):
        """Test getting ticket by ID"""
        try:
            # First get a ticket ID from the list
            response = self.session.get(f"{API_BASE}/tickets?per_page=1", timeout=10)
            if response.status_code == 200:
                data = response.json()
                if data['data'] and len(data['data']) > 0:
                    ticket_id = data['data'][0]['id']
                    
                    # Now test getting this specific ticket
                    response = self.session.get(f"{API_BASE}/tickets/{ticket_id}", timeout=10)
                    if response.status_code == 200:
                        ticket_data = response.json()
                        if ticket_data.get('id') == ticket_id:
                            self.log_result("Get Ticket by ID", True, f"Ticket {ticket_id} retrieved successfully", {
                                'id': ticket_data['id'],
                                'subject': ticket_data.get('subject'),
                                'status': ticket_data.get('status'),
                                'has_client': 'client' in ticket_data,
                                'has_replies': 'replies' in ticket_data
                            })
                            return True
                        else:
                            self.log_result("Get Ticket by ID", False, f"ID mismatch in response", ticket_data)
                            return False
                    else:
                        self.log_result("Get Ticket by ID", False, f"HTTP {response.status_code}: {response.text}")
                        return False
                else:
                    self.log_result("Get Ticket by ID", False, "No tickets found to test with")
                    return False
            else:
                self.log_result("Get Ticket by ID", False, f"Failed to get ticket list: HTTP {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Get Ticket by ID", False, f"Connection error: {str(e)}")
            return False
    
    def run_all_tests(self):
        """Run all Laravel backend tests"""
        print(f"🚀 Starting Laravel WHMCS Backend API Tests")
        print(f"📍 Testing API at: {API_BASE}")
        print("=" * 60)
        
        tests = [
            self.test_dashboard_endpoint,
            self.test_clients_list,
            self.test_clients_search,
            self.test_clients_status_filter,
            self.test_client_by_id,
            self.test_services_list,
            self.test_services_status_filter,
            self.test_service_by_id,
            self.test_domains_list,
            self.test_domains_status_filter,
            self.test_domain_by_id,
            self.test_invoices_list,
            self.test_invoices_status_filter,
            self.test_invoice_by_id,
            self.test_tickets_list,
            self.test_ticket_by_id
        ]
        
        passed = 0
        failed = 0
        
        for test in tests:
            try:
                if test():
                    passed += 1
                else:
                    failed += 1
            except Exception as e:
                print(f"❌ FAIL {test.__name__}: Unexpected error: {str(e)}")
                failed += 1
            print()  # Empty line for readability
        
        print("=" * 60)
        print(f"📊 Test Results Summary:")
        print(f"✅ Passed: {passed}")
        print(f"❌ Failed: {failed}")
        print(f"📈 Success Rate: {(passed/(passed+failed)*100):.1f}%")
        
        if failed > 0:
            print("\n🔍 Failed Tests Details:")
            for result in self.test_results:
                if not result['success']:
                    print(f"   • {result['test']}: {result['details']}")
        
        return passed, failed

def main():
    """Main test runner"""
    tester = LaravelBackendTester()
    passed, failed = tester.run_all_tests()
    
    # Exit with error code if tests failed
    sys.exit(0 if failed == 0 else 1)

if __name__ == "__main__":
    main()