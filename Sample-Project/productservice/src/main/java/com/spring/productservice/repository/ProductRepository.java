package com.spring.productservice.repository;

import com.spring.productservice.model.Product;
import org.springframework.data.mongodb.repository.MongoRepository;

public interface ProductRepository  extends MongoRepository<Product,Integer> {
}
