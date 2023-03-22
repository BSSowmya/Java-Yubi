package com.springrest.springrest.dao;

import com.springrest.springrest.entities.Manager;
import org.springframework.data.jpa.repository.JpaRepository;

public interface ManagerDao  extends JpaRepository<Manager,Long> {
}
