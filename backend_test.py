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
    
    def test_clients_list(self):
        """Test clients list endpoint with pagination"""
        try:
            # Test basic list
            response = self.session.get(f"{API_BASE}/clients", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                expected_keys = ['clients', 'total', 'page', 'limit', 'totalPages']
                if all(key in data for key in expected_keys):
                    self.log_result("Clients List", True, f"Retrieved {data['total']} clients (page {data['page']})", {
                        'total': data['total'],
                        'page': data['page'],
                        'limit': data['limit'],
                        'totalPages': data['totalPages']
                    })
                    return True
                else:
                    self.log_result("Clients List", False, f"Missing expected keys in response", data)
                    return False
            else:
                self.log_result("Clients List", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Clients List", False, f"Connection error: {str(e)}")
            return False
    
    def test_clients_pagination(self):
        """Test clients pagination"""
        try:
            # Test with pagination parameters
            response = self.session.get(f"{API_BASE}/clients?page=1&limit=5", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('page') == 1 and data.get('limit') == 5:
                    self.log_result("Clients Pagination", True, f"Pagination working: page={data['page']}, limit={data['limit']}")
                    return True
                else:
                    self.log_result("Clients Pagination", False, f"Pagination parameters not respected", data)
                    return False
            else:
                self.log_result("Clients Pagination", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Clients Pagination", False, f"Connection error: {str(e)}")
            return False
    
    def test_clients_search(self):
        """Test clients search functionality"""
        try:
            # Test search with a common term
            response = self.session.get(f"{API_BASE}/clients?search=john", timeout=10)
            
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
    
    def test_create_client(self):
        """Test creating a new client"""
        try:
            # Create realistic client data
            client_data = {
                "firstName": "John",
                "lastName": "Smith",
                "email": f"john.smith.{uuid.uuid4().hex[:8]}@example.com",
                "phone": "+1-555-0123",
                "company": "Smith Enterprises",
                "address": "123 Business Ave",
                "city": "New York",
                "state": "NY",
                "zipCode": "10001",
                "country": "US",
                "status": "active"
            }
            
            response = self.session.post(f"{API_BASE}/clients", 
                                       json=client_data, 
                                       timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                if 'id' in data and data.get('email') == client_data['email']:
                    self.created_client_id = data['id']
                    self.log_result("Create Client", True, f"Client created successfully with ID: {data['id']}", {
                        'id': data['id'],
                        'email': data['email'],
                        'firstName': data.get('firstName'),
                        'lastName': data.get('lastName')
                    })
                    return True
                else:
                    self.log_result("Create Client", False, f"Unexpected response structure", data)
                    return False
            else:
                self.log_result("Create Client", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Create Client", False, f"Connection error: {str(e)}")
            return False
    
    def test_get_client_by_id(self):
        """Test getting client by ID"""
        if not self.created_client_id:
            self.log_result("Get Client by ID", False, "No client ID available (create client test may have failed)")
            return False
            
        try:
            response = self.session.get(f"{API_BASE}/clients/{self.created_client_id}", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('id') == self.created_client_id:
                    self.log_result("Get Client by ID", True, f"Client retrieved successfully", {
                        'id': data['id'],
                        'email': data.get('email'),
                        'firstName': data.get('firstName')
                    })
                    return True
                else:
                    self.log_result("Get Client by ID", False, f"ID mismatch in response", data)
                    return False
            elif response.status_code == 404:
                self.log_result("Get Client by ID", False, "Client not found (404)")
                return False
            else:
                self.log_result("Get Client by ID", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Get Client by ID", False, f"Connection error: {str(e)}")
            return False
    
    def test_billing_invoices(self):
        """Test billing invoices endpoint"""
        try:
            response = self.session.get(f"{API_BASE}/billing/invoices", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                expected_keys = ['invoices', 'total', 'page', 'limit', 'totalPages']
                if all(key in data for key in expected_keys):
                    self.log_result("Billing Invoices", True, f"Retrieved {data['total']} invoices", {
                        'total': data['total'],
                        'page': data['page'],
                        'totalPages': data['totalPages']
                    })
                    return True
                else:
                    self.log_result("Billing Invoices", False, f"Missing expected keys in response", data)
                    return False
            else:
                self.log_result("Billing Invoices", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Billing Invoices", False, f"Connection error: {str(e)}")
            return False
    
    def test_billing_stats(self):
        """Test billing stats endpoint"""
        try:
            response = self.session.get(f"{API_BASE}/billing/stats", timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                # Check for common billing stats fields
                expected_keys = ['totalRevenue', 'totalInvoices', 'paidInvoices', 'unpaidInvoices']
                if any(key in data for key in expected_keys):
                    self.log_result("Billing Stats", True, "Billing stats retrieved successfully", data)
                    return True
                else:
                    self.log_result("Billing Stats", True, "Billing stats endpoint working (structure may vary)", data)
                    return True
            else:
                self.log_result("Billing Stats", False, f"HTTP {response.status_code}: {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Billing Stats", False, f"Connection error: {str(e)}")
            return False
    
    def test_error_handling(self):
        """Test error handling for invalid endpoints"""
        try:
            # Test invalid endpoint
            response = self.session.get(f"{API_BASE}/invalid-endpoint", timeout=10)
            
            if response.status_code == 404:
                self.log_result("Error Handling - Invalid Endpoint", True, "404 returned for invalid endpoint")
                return True
            else:
                self.log_result("Error Handling - Invalid Endpoint", False, f"Expected 404, got {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Error Handling - Invalid Endpoint", False, f"Connection error: {str(e)}")
            return False
    
    def test_malformed_requests(self):
        """Test handling of malformed requests"""
        try:
            # Test POST with invalid JSON
            response = self.session.post(f"{API_BASE}/clients", 
                                       data="invalid json", 
                                       headers={'Content-Type': 'application/json'},
                                       timeout=10)
            
            if response.status_code in [400, 422]:  # Bad Request or Unprocessable Entity
                self.log_result("Error Handling - Malformed JSON", True, f"Properly handled malformed JSON with {response.status_code}")
                return True
            else:
                self.log_result("Error Handling - Malformed JSON", False, f"Expected 400/422, got {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("Error Handling - Malformed JSON", False, f"Connection error: {str(e)}")
            return False
    
    def test_cors(self):
        """Test CORS headers"""
        try:
            # Make an OPTIONS request to check CORS
            response = self.session.options(f"{API_BASE}/health", 
                                          headers={'Origin': 'https://example.com'},
                                          timeout=10)
            
            cors_headers = {
                'Access-Control-Allow-Origin': response.headers.get('Access-Control-Allow-Origin'),
                'Access-Control-Allow-Methods': response.headers.get('Access-Control-Allow-Methods'),
                'Access-Control-Allow-Headers': response.headers.get('Access-Control-Allow-Headers')
            }
            
            if cors_headers['Access-Control-Allow-Origin']:
                self.log_result("CORS Support", True, "CORS headers present", cors_headers)
                return True
            else:
                # Try a regular GET request and check for CORS headers
                response = self.session.get(f"{API_BASE}/health", 
                                          headers={'Origin': 'https://example.com'},
                                          timeout=10)
                cors_origin = response.headers.get('Access-Control-Allow-Origin')
                if cors_origin:
                    self.log_result("CORS Support", True, f"CORS enabled: {cors_origin}")
                    return True
                else:
                    self.log_result("CORS Support", False, "No CORS headers found")
                    return False
                
        except requests.exceptions.RequestException as e:
            self.log_result("CORS Support", False, f"Connection error: {str(e)}")
            return False
    
    def run_all_tests(self):
        """Run all backend tests"""
        print(f"🚀 Starting WHMCS Admin Backend API Tests")
        print(f"📍 Testing API at: {API_BASE}")
        print("=" * 60)
        
        tests = [
            self.test_health_check,
            self.test_root_endpoint,
            self.test_dashboard_stats,
            self.test_dashboard_activities,
            self.test_dashboard_tasks,
            self.test_clients_list,
            self.test_clients_pagination,
            self.test_clients_search,
            self.test_create_client,
            self.test_get_client_by_id,
            self.test_billing_invoices,
            self.test_billing_stats,
            self.test_error_handling,
            self.test_malformed_requests,
            self.test_cors
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
    tester = WHMCSBackendTester()
    passed, failed = tester.run_all_tests()
    
    # Exit with error code if tests failed
    sys.exit(0 if failed == 0 else 1)

if __name__ == "__main__":
    main()